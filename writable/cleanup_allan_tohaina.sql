-- =============================================================
-- SCRIPT DE NETTOYAGE COMPLET : COMPTE "ALLAN TOHAINA"
-- Usage : Exécuter dans phpMyAdmin / Adminer / MySQL CLI
-- Base cible : jmrtexti_xxx (modifier éventuellement USE ci-dessous)
-- =============================================================

-- Étape 0 : Identifier l'UUID du compte Allan Tohaina
-- Copier la valeur de @user_id renvoyée et vérifier qu'elle n'est pas NULL
-- IMPORTANT: TRUNCATE cannot target a single client. This script uses
-- targeted DELETE statements and leaves all other accounts untouched.
START TRANSACTION;

SET @user_id := NULL;
SELECT id, email, first_name, last_name, role INTO @user_id, @email, @fname, @lname, @role
FROM users
WHERE deleted_at IS NULL
  AND (
    (LOWER(first_name) LIKE '%allan%' AND LOWER(last_name) LIKE '%tohaina%')
    OR LOWER(CONCAT(first_name, ' ', last_name)) LIKE '%allan tohaina%'
  )
LIMIT 1;

-- DEBUG : Afficher les infos du compte trouvé
SELECT @user_id AS user_id, @email AS email, @fname AS prenom, @lname AS nom, @role AS role;

-- =============================================================
-- Si @user_id est NULL, vérifier manuellement :
-- SELECT id, first_name, last_name, email FROM users WHERE deleted_at IS NULL;
-- Puis faire : SET @user_id := 'UUID-TROUVE'; SET @email := 'email@ex.com';
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================
-- 1) TOKENS & SESSIONS
-- =============================================================
DELETE FROM token_history         WHERE user_id = @user_id OR refresh_token_id IN (SELECT id FROM refresh_tokens WHERE user_id = @user_id);
DELETE FROM refresh_tokens        WHERE user_id = @user_id;
-- token_blacklist does not identify users: it must not be purged here.
DELETE FROM push_subscriptions    WHERE user_id = @user_id;

-- =============================================================
-- 2) NOTIFICATIONS (recipient OU actor)
-- =============================================================
DELETE FROM notifications         WHERE recipient_user_id = @user_id OR actor_user_id = @user_id;

-- =============================================================
-- 3) RGPD : consentements & demandes d'accès
-- =============================================================
DELETE FROM user_consents         WHERE user_id = @user_id;
DELETE FROM data_requests         WHERE user_id = @user_id;

-- =============================================================
-- 4) AUDIT LOGS (actor ET entités liées)
-- =============================================================
-- actor
DELETE FROM audit_logs            WHERE actor_user_id = @user_id;

-- =============================================================
-- 5) LIENS PAIEMENT, POINTS FIDÉLITÉ, AVIS PRODUITS
-- =============================================================
DELETE FROM liens_paiement        WHERE user_id = @user_id OR client_email = @email;
DELETE FROM points_fidelite       WHERE user_id = @user_id;
DELETE FROM avis_produits         WHERE user_id = @user_id OR (email IS NOT NULL AND email = @email);

-- =============================================================
-- 6) PIÈCES JOINTES (attachments uploaded_by OU liées à ses devis/commandes/demandes)
-- =============================================================
DELETE FROM attachments
 WHERE uploaded_by = @user_id
    OR (entity_type = 'quote'          AND entity_id IN (SELECT id FROM quotes WHERE client_id = @user_id OR email = @email))
    OR (entity_type = 'commande'       AND entity_id IN (SELECT id FROM commandes WHERE client_id = @user_id))
    OR (entity_type = 'demande_client' AND entity_id IN (SELECT id FROM demandes_client WHERE email = @email OR nom_client LIKE CONCAT(@fname, '%', @lname)));

-- =============================================================
-- 7) PRODUCTION (workflows, tickets, tasks, checklists, assemblages, history)
--    Rattrapés par commande_id ou actor_user_id
-- =============================================================
DELETE FROM production_tasks
 WHERE assigned_worker_id = @user_id
    OR ticket_id IN (SELECT id FROM production_tickets WHERE commande_id IN (SELECT id FROM commandes WHERE client_id = @user_id));

DELETE FROM production_tickets    WHERE commande_id IN (SELECT id FROM commandes WHERE client_id = @user_id) OR created_by = @user_id;
DELETE FROM order_project_history WHERE actor_user_id = @user_id OR project_id IN (SELECT id FROM production_workflows WHERE client_name LIKE CONCAT(@fname, '%', @lname));
DELETE FROM production_checklists WHERE project_id IN (SELECT id FROM production_workflows WHERE client_name LIKE CONCAT(@fname, '%', @lname));
DELETE FROM assemblages           WHERE project_id IN (SELECT id FROM production_workflows WHERE client_name LIKE CONCAT(@fname, '%', @lname));
DELETE FROM production_workflows  WHERE client_name LIKE CONCAT(@fname, '%', @lname);

-- =============================================================
-- 8) BONS DE LIVRAISON (dépend de commandes)
-- =============================================================
DELETE FROM bons_livraison        WHERE commande_id IN (SELECT id FROM commandes WHERE client_id = @user_id);

-- =============================================================
-- 9) PAIEMENTS (submitted_by, reviewed_by, quote_id liés)
-- =============================================================
DELETE FROM payments
 WHERE submitted_by = @user_id
    OR reviewed_by = @user_id
    OR (client_email IS NOT NULL AND client_email = @email)
    OR quote_id IN (SELECT id FROM quotes WHERE client_id = @user_id OR email = @email);

-- =============================================================
-- 10) CHECKPOINTS & ADDONS devis (liés à commande_id)
-- =============================================================
DELETE FROM quote_checkpoints     WHERE commande_id IN (SELECT id FROM commandes WHERE client_id = @user_id);
DELETE FROM quote_addons          WHERE commande_id IN (SELECT id FROM commandes WHERE client_id = @user_id);

-- =============================================================
-- 11) COMMANDES (client_id)
-- =============================================================
DELETE FROM commandes             WHERE client_id = @user_id;

-- =============================================================
-- 12) DEMANDES CLIENT (email ou nom)
-- =============================================================
DELETE FROM demandes_client
 WHERE email = @email
    OR (nom_client IS NOT NULL AND nom_client LIKE CONCAT(@fname, '%', @lname));

-- =============================================================
-- 13) DEVIS (client_id OU email OU name)
-- =============================================================
DELETE FROM quotes
 WHERE client_id = @user_id
    OR email = @email
    OR (name IS NOT NULL AND name LIKE CONCAT(@fname, '%', @lname));

-- =============================================================
-- 14) AUDIT LOGS restants : entités supprimées ci-dessus
-- =============================================================
DELETE FROM audit_logs
 WHERE (entity_type = 'quote'          AND entity_id NOT IN (SELECT id FROM quotes))
    OR (entity_type = 'commande'       AND entity_id NOT IN (SELECT id FROM commandes))
    OR (entity_type = 'demande_client' AND entity_id NOT IN (SELECT id FROM demandes_client));

-- =============================================================
-- 15) USER : HARD DELETE (ou remplacer par soft delete)
--    Soft delete : UPDATE users SET deleted_at = NOW() WHERE id = @user_id;
-- =============================================================
-- Soft delete par défaut (convention métier : on ne supprime jamais vraiment)
UPDATE users SET deleted_at = NOW(), updated_at = NOW() WHERE id = @user_id;
-- Si hard delete préféré : DELETE FROM users WHERE id = @user_id;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- FIN : Vérification
-- =============================================================
SELECT 'Utilisateurs restants Allan/Tohaina :' AS info;
SELECT id, first_name, last_name, email, role, deleted_at
  FROM users
 WHERE (LOWER(first_name) LIKE '%allan%' AND LOWER(last_name) LIKE '%tohaina%')
    OR LOWER(CONCAT(first_name, ' ', last_name)) LIKE '%allan tohaina%';

COMMIT;
