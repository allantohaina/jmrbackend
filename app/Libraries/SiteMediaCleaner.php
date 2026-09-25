<?php

namespace App\Libraries;

// Gestion radicale des médias du crayon (uploads/site/) :
// quand une image/vidéo est remplacée, l'ancien fichier est supprimé
// pour de bon — pas d'accumulation, pas de retour en arrière.
// Un fichier n'est supprimé que s'il n'est référencé nulle part
// (tous locales/clés + autres tables pouvant stocker une URL).
class SiteMediaCleaner
{
    public const DIR = 'uploads/site/';

    /** Extensions produites par MediaController::finalizeUpload. */
    private const ALLOWED_EXTS = ['jpg', 'jpeg', 'mp4', 'webm'];

    /**
     * Extrait un nom de fichier sûr depuis une valeur CMS quelconque.
     * Retourne null pour les textes et tout ce qui n'est pas un fichier site.
     */
    public static function filenameFromValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $marker = self::DIR;
        $idx = strpos($value, $marker);
        if ($idx === false) {
            return null;
        }
        $filename = substr($value, $idx + strlen($marker));
        $filename = preg_split('/[?#]/', $filename)[0];
        // Nom simple uniquement : pas de dossier, pas de "..", extension connue.
        if ($filename === '' || basename($filename) !== $filename) {
            return null;
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTS, true)) {
            return null;
        }
        return $filename;
    }

    /** Chemin absolu du fichier, ou null si hors du dossier site (paranoïa). */
    public static function absolutePath(string $filename): ?string
    {
        $base = realpath(FCPATH . self::DIR);
        if ($base === false) {
            return null;
        }
        $path = $base . DIRECTORY_SEPARATOR . $filename;
        $real = realpath($path);
        // realpath échoue si le fichier n'existe pas : on vérifie le parent.
        if ($real === false) {
            if (basename($filename) !== $filename) {
                return null;
            }
            return $path;
        }
        if (strpos($real, $base . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }
        return $real;
    }

    /**
     * Vrai si le fichier est encore référencé quelque part en base
     * (contenus CMS tous locales + pièces jointes + produits + users + devis).
     */
    public static function isReferenced(string $filename): bool
    {
        $db = \Config\Database::connect();

        $checks = [
            ['site_contents', 'value'],
            ['attachments', 'url'],
            ['attachments', 'storage_path'],
            ['produits', 'photo_url'],
            ['users', 'profile_image'],
            ['quotes', 'files'],
        ];

        foreach ($checks as [$table, $column]) {
            try {
                if (!$db->tableExists($table)) {
                    continue;
                }
                $count = $db->table($table)->like($column, $filename, 'both')->countAllResults();
                if ($count > 0) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Table/colonne absente ou illisible : on ne supprime pas
                // sur un doute. Le fichier sera revu au prochain passage.
                log_message('error', 'SiteMediaCleaner::isReferenced ' . $table . '.' . $column . ': ' . $e->getMessage());
                return true;
            }
        }

        return false;
    }

    /**
     * Supprime le fichier s'il est orphelin. Retourne true si le fichier
     * n'existe plus après l'appel (supprimé ou déjà absent).
     */
    public static function deleteIfOrphan(string $filename): bool
    {
        $path = self::absolutePath($filename);
        if ($path === null || !is_file($path)) {
            return true;
        }
        if (self::isReferenced($filename)) {
            return false;
        }
        return @unlink($path);
    }

    /**
     * Après remplacement d'une valeur CMS : supprime l'ancien fichier
     * s'il diffère du nouveau et n'est plus référencé. Ne lève jamais.
     */
    public static function collectReplaced(?string $oldValue, ?string $newValue): void
    {
        try {
            $oldFile = self::filenameFromValue($oldValue);
            if ($oldFile === null) {
                return;
            }
            $newFile = self::filenameFromValue($newValue);
            if ($newFile !== null && $newFile === $oldFile) {
                return;
            }
            if (!self::deleteIfOrphan($oldFile)) {
                log_message('info', 'SiteMediaCleaner: conservé (encore référencé) : ' . $oldFile);
            }
        } catch (\Throwable $e) {
            // Le nettoyage ne doit jamais faire échouer la sauvegarde.
            log_message('error', 'SiteMediaCleaner::collectReplaced: ' . $e->getMessage());
        }
    }
}
