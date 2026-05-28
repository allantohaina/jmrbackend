<?php

namespace App\Application\Hr;

use App\Application\Shared\Result;

class HrService
{
    public function lookup(string $name): Result
    {
        $catalog = $this->lookupCatalog();

        if (!array_key_exists($name, $catalog)) {
            return Result::notFound('Ressource RH introuvable.');
        }

        return Result::ok($catalog[$name]);
    }

    public function departmentPostes(string $departmentId): Result
    {
        $positions = $this->departmentPositions();

        return Result::ok($positions[$departmentId] ?? []);
    }

    public function departmentManager(string $departmentId): Result
    {
        $managers = $this->departmentManagers();

        if (!array_key_exists($departmentId, $managers)) {
            return Result::notFound('Aucun manager associe a ce departement.');
        }

        return Result::ok($managers[$departmentId]);
    }

    public function createEmploye(array $payload): Result
    {
        $employe = is_array($payload['employe'] ?? null) ? $payload['employe'] : [];
        $infosProfessionnelles = is_array($payload['infosProfessionnelles'] ?? null)
            ? $payload['infosProfessionnelles']
            : [];
        $premiereInfoPro = isset($infosProfessionnelles[0]) && is_array($infosProfessionnelles[0])
            ? $infosProfessionnelles[0]
            : [];

        $missing = [];

        foreach (['nom', 'prenom', 'email', 'telephone', 'cin'] as $field) {
            if (trim((string) ($employe[$field] ?? '')) === '') {
                $missing[] = "employe.{$field}";
            }
        }

        if (trim((string) ($payload['region']['id'] ?? '')) === '') {
            $missing[] = 'region.id';
        }

        if (trim((string) ($payload['sexe']['id'] ?? '')) === '') {
            $missing[] = 'sexe.id';
        }

        if (trim((string) ($payload['nationalite']['id'] ?? '')) === '') {
            $missing[] = 'nationalite.id';
        }

        $matricule = trim((string) ($premiereInfoPro['matricule'] ?? ''));
        if ($matricule === '') {
            $missing[] = 'infosProfessionnelles.0.matricule';
        }

        if (trim((string) ($premiereInfoPro['dateEmbauche'] ?? '')) === '') {
            $missing[] = 'infosProfessionnelles.0.dateEmbauche';
        }

        if (trim((string) (($premiereInfoPro['departement']['id'] ?? ''))) === '') {
            $missing[] = 'infosProfessionnelles.0.departement.id';
        }

        if (trim((string) (($premiereInfoPro['poste']['id'] ?? ''))) === '') {
            $missing[] = 'infosProfessionnelles.0.poste.id';
        }

        if (empty($infosProfessionnelles)) {
            $missing[] = 'infosProfessionnelles';
        }

        if (!empty($missing)) {
            return Result::fail([
                'message' => 'Certains champs obligatoires sont manquants pour creer le dossier employe.',
                'missing' => $missing,
            ], 400);
        }

        $records = $this->readEmployees();
        $email = strtolower(trim((string) ($employe['email'] ?? '')));
        $cin = trim((string) ($employe['cin'] ?? ''));

        foreach ($records as $record) {
            $recordEmail = strtolower(trim((string) (($record['employe']['email'] ?? ''))));
            $recordCin = trim((string) (($record['employe']['cin'] ?? '')));
            $recordMatricule = $this->extractMatricule($record);

            if ($recordEmail !== '' && $recordEmail === $email) {
                return Result::fail([
                    'message' => 'Un employe avec cet e-mail existe deja.',
                ], 409);
            }

            if ($recordCin !== '' && $recordCin === $cin) {
                return Result::fail([
                    'message' => 'Un employe avec ce CIN existe deja.',
                ], 409);
            }

            if ($recordMatricule !== null && $recordMatricule === $matricule) {
                return Result::fail([
                    'message' => 'Ce matricule est deja utilise.',
                ], 409);
            }
        }

        $employeeId = $this->generateEmployeeId($records);
        $reference = $this->generateReference($employeeId);
        $timestamp = gmdate('c');

        $record = [
            'id' => $employeeId,
            'reference' => $reference,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'employe' => $employe,
            'region' => $payload['region'] ?? null,
            'sexe' => $payload['sexe'] ?? null,
            'nationalite' => $payload['nationalite'] ?? null,
            'infosAdministratives' => $payload['infosAdministratives'] ?? null,
            'emergencyContact' => $payload['emergencyContact'] ?? null,
            'infosProfessionnelles' => $infosProfessionnelles,
            'modePaiements' => is_array($payload['modePaiements'] ?? null) ? $payload['modePaiements'] : [],
        ];

        $records[] = $record;

        if (!$this->writeEmployees($records)) {
            return Result::fail([
                'message' => 'Impossible de sauvegarder le dossier employe.',
            ], 500);
        }

        return Result::created([
            'message' => 'Employe enregistre avec succes.',
            'data' => $record,
        ]);
    }

    private function storagePath(): string
    {
        return WRITEPATH . 'data/hr/employes.json';
    }

    private function readEmployees(): array
    {
        $path = $this->storagePath();

        if (!is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        if ($contents === false || trim($contents) === '') {
            return [];
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeEmployees(array $records): bool
    {
        $path = $this->storagePath();
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            return false;
        }

        $json = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return false;
        }

        return file_put_contents($path, $json, LOCK_EX) !== false;
    }

    private function extractMatricule(array $record): ?string
    {
        $infosProfessionnelles = is_array($record['infosProfessionnelles'] ?? null)
            ? $record['infosProfessionnelles']
            : [];
        $premiereInfo = isset($infosProfessionnelles[0]) && is_array($infosProfessionnelles[0])
            ? $infosProfessionnelles[0]
            : [];

        $matricule = trim((string) ($premiereInfo['matricule'] ?? ''));

        return $matricule === '' ? null : $matricule;
    }

    private function generateEmployeeId(array $records): string
    {
        return 'EMP-' . str_pad((string) (count($records) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function generateReference(string $employeeId): string
    {
        return sprintf('RH-%s-%s', gmdate('Ymd'), $employeeId);
    }

    private function lookupCatalog(): array
    {
        return [
            'sexes' => [
                ['id' => 'SEX001', 'sexe' => 'Masculin'],
                ['id' => 'SEX002', 'sexe' => 'Feminin'],
            ],
            'type-contrats' => [
                ['id' => 'CONT001', 'intitule' => 'CDI'],
                ['id' => 'CONT002', 'intitule' => 'CDD'],
                ['id' => 'CONT003', 'intitule' => 'Stage'],
                ['id' => 'CONT004', 'intitule' => 'Prestataire'],
                ['id' => 'CONT008', 'intitule' => 'Saisonnier'],
                ['id' => 'CONT010', 'intitule' => 'Mission'],
            ],
            'departements' => [
                ['id' => 'DEP001', 'nom' => 'Coupe'],
                ['id' => 'DEP002', 'nom' => 'Assemblage'],
                ['id' => 'DEP003', 'nom' => 'Finition'],
                ['id' => 'DEP004', 'nom' => 'Qualite'],
                ['id' => 'DEP005', 'nom' => 'Logistique'],
                ['id' => 'DEP006', 'nom' => 'Administration et RH'],
            ],
            'nationalites' => [
                ['id' => 'NAT001', 'nationalite' => 'Malagasy'],
                ['id' => 'NAT002', 'nationalite' => 'Francaise'],
                ['id' => 'NAT003', 'nationalite' => 'Mauricienne'],
            ],
            'regions' => [
                ['id' => 'REG001', 'nom' => 'Analamanga'],
                ['id' => 'REG002', 'nom' => 'Atsinanana'],
                ['id' => 'REG003', 'nom' => 'Vakinankaratra'],
                ['id' => 'REG004', 'nom' => 'Boeny'],
            ],
            'categories-professionnelles' => [
                ['id' => 'CAT001', 'code' => 'OUV', 'libelle' => 'Ouvrier'],
                ['id' => 'CAT002', 'code' => 'TEC', 'libelle' => 'Technicien'],
                ['id' => 'CAT003', 'code' => 'AGM', 'libelle' => 'Agent de maitrise'],
                ['id' => 'CAT004', 'code' => 'CAD', 'libelle' => 'Cadre'],
            ],
            'types-temps-travail' => [
                ['id' => 'TT001', 'tempsTravail' => 'Temps plein'],
                ['id' => 'TT002', 'tempsTravail' => 'Mi-temps'],
                ['id' => 'TT003', 'tempsTravail' => 'Temps partiel amenage'],
            ],
            'types-entree' => [
                ['id' => 'ENT001', 'typeEntree' => 'Recrutement direct'],
                ['id' => 'ENT002', 'typeEntree' => 'Mutation interne'],
                ['id' => 'ENT003', 'typeEntree' => 'Retour de mission'],
            ],
            'types-paiement' => [
                ['id' => 'PAY001', 'typePaiement' => 'Mobile Money'],
                ['id' => 'PAY002', 'typePaiement' => 'Virement bancaire'],
                ['id' => 'PAY003', 'typePaiement' => 'Especes'],
            ],
        ];
    }

    private function departmentPositions(): array
    {
        return [
            'DEP001' => [
                ['id' => 'POS001', 'nom' => 'Agent de coupe'],
                ['id' => 'POS002', 'nom' => 'Chef de coupe'],
            ],
            'DEP002' => [
                ['id' => 'POS003', 'nom' => 'Operateur assemblage'],
                ['id' => 'POS004', 'nom' => 'Chef de ligne assemblage'],
            ],
            'DEP003' => [
                ['id' => 'POS005', 'nom' => 'Agent finition'],
                ['id' => 'POS006', 'nom' => 'Referent finition'],
            ],
            'DEP004' => [
                ['id' => 'POS007', 'nom' => 'Controleur qualite'],
                ['id' => 'POS008', 'nom' => 'Responsable qualite'],
            ],
            'DEP005' => [
                ['id' => 'POS009', 'nom' => 'Magasinier'],
                ['id' => 'POS010', 'nom' => 'Coordinateur logistique'],
            ],
            'DEP006' => [
                ['id' => 'POS011', 'nom' => 'Assistant RH'],
                ['id' => 'POS012', 'nom' => 'Responsable administratif'],
            ],
        ];
    }

    private function departmentManagers(): array
    {
        return [
            'DEP001' => [
                'id' => 'MGR-DEP001',
                'first_name' => 'Miora',
                'last_name' => 'Rakoto',
            ],
            'DEP002' => [
                'id' => 'MGR-DEP002',
                'first_name' => 'Aina',
                'last_name' => 'Rasoanaivo',
            ],
            'DEP003' => [
                'id' => 'MGR-DEP003',
                'first_name' => 'Lova',
                'last_name' => 'Andriamihaingo',
            ],
            'DEP004' => [
                'id' => 'MGR-DEP004',
                'first_name' => 'Sarah',
                'last_name' => 'Ramilison',
            ],
            'DEP005' => [
                'id' => 'MGR-DEP005',
                'first_name' => 'Tahina',
                'last_name' => 'Ravelo',
            ],
            'DEP006' => [
                'id' => 'MGR-DEP006',
                'first_name' => 'Hery',
                'last_name' => 'Ramanantsoa',
            ],
        ];
    }
}
