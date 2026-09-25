<?php

namespace App\Commands;

use App\Libraries\SiteMediaCleaner;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

// Purge unique des orphelins de uploads/site/ (anciennes images/vidéos
// du crayon déjà remplacées mais jamais supprimées) + morceaux d'upload
// abandonnés. Lecture seule par défaut ; suppression avec --apply.
class CleanSiteMediaOrphans extends BaseCommand
{
    protected $group       = 'Media';
    protected $name        = 'media:clean-orphans';
    protected $description = 'Liste (et avec --apply supprime) les médias orphelins de uploads/site/';
    protected $usage       = 'media:clean-orphans [--apply]';

    public function run(array $params)
    {
        $apply = in_array('--apply', $params, true) || array_key_exists('apply', $params);

        $dir = FCPATH . SiteMediaCleaner::DIR;
        if (!is_dir($dir)) {
            CLI::write('Dossier introuvable : ' . $dir, 'yellow');
            return;
        }

        $files = array_values(array_filter(
            scandir($dir),
            static function ($name) use ($dir) {
                if ($name === '.' || $name === '..') {
                    return false;
                }
                // .htaccess / index.html de protection : jamais touchés.
                if ($name[0] === '.') {
                    return false;
                }
                return is_file($dir . $name);
            }
        ));

        if (empty($files)) {
            CLI::write('Aucun fichier dans ' . SiteMediaCleaner::DIR, 'green');
        } else {
            $orphans = [];
            $orphanBytes = 0;
            foreach ($files as $name) {
                if (SiteMediaCleaner::filenameFromValue(SiteMediaCleaner::DIR . $name) === null) {
                    continue; // Nom inattendu : on ne touche pas.
                }
                if (SiteMediaCleaner::isReferenced($name)) {
                    continue;
                }
                $orphans[] = $name;
                $orphanBytes += filesize($dir . $name);
            }

            if (empty($orphans)) {
                CLI::write(count($files) . ' fichier(s), tous référencés. Rien à faire.', 'green');
            } else {
                CLI::write(count($orphans) . ' orphelin(s), ' . $this->humanSize($orphanBytes) . ' :', 'yellow');
                foreach ($orphans as $name) {
                    if ($apply) {
                        $ok = SiteMediaCleaner::deleteIfOrphan($name);
                        CLI::write(($ok ? '  [supprimé] ' : '  [ÉCHEC] ') . $name, $ok ? 'red' : 'white');
                    } else {
                        CLI::write('  ' . $name, 'white');
                    }
                }
                if (!$apply) {
                    CLI::write('Lecture seule. Relancer avec --apply pour supprimer.', 'yellow');
                }
            }
        }

        // Morceaux d'uploads interrompus (chunks vieux de +24h).
        $chunksBase = WRITEPATH . 'chunks/';
        $staleDirs = 0;
        if (is_dir($chunksBase)) {
            foreach (scandir($chunksBase) as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $path = $chunksBase . $entry;
                if (!is_dir($path)) {
                    continue;
                }
                if (filemtime($path) !== false && filemtime($path) < time() - 86400) {
                    $staleDirs++;
                    if ($apply) {
                        foreach (glob($path . '/chunk_*') ?: [] as $chunk) {
                            @unlink($chunk);
                        }
                        @rmdir($path);
                    }
                }
            }
        }
        CLI::write(
            $staleDirs . ' dossier(s) de morceaux abandonnés (+24h)' . ($apply ? ' supprimés.' : ' (voir --apply).'),
            $staleDirs > 0 ? 'yellow' : 'green'
        );
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' o';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' Ko';
        }
        return round($bytes / 1048576, 1) . ' Mo';
    }
}
