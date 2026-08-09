<?php

namespace Config;

use App\Application\History\TokenHistory\LogTokenHistoryUseCase;
use App\Application\Achats\AchatService;
use App\Application\Attachments\AttachmentService;
use App\Application\BonLivraison\BonLivraisonService;
use App\Application\Commandes\CommandeService;
use App\Application\DemandesClient\DemandeClientService;
use App\Application\Produits\ProduitService;
use App\Application\Production\Assemblage\AssemblageService;
use App\Application\Production\Checklist\ChecklistService;
use App\Application\Production\Workflow\ProductionWorkflowService;
use App\Infrastructure\History\Persistence\TokenHistoryRepository;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function checklistService($getShared = true): ChecklistService
    {
        if ($getShared) {
            return static::getSharedInstance('checklistService');
        }

        return new ChecklistService();
    }

    public static function productionWorkflowService($getShared = true): ProductionWorkflowService
    {
        if ($getShared) {
            return static::getSharedInstance('productionWorkflowService');
        }

        return new ProductionWorkflowService();
    }

    public static function assemblageService($getShared = true): AssemblageService
    {
        if ($getShared) {
            return static::getSharedInstance('assemblageService');
        }

        return new AssemblageService();
    }

    public static function commandeService($getShared = true): CommandeService
    {
        if ($getShared) {
            return static::getSharedInstance('commandeService');
        }

        return new CommandeService();
    }

    public static function achatService($getShared = true): AchatService
    {
        if ($getShared) {
            return static::getSharedInstance('achatService');
        }

        return new AchatService();
    }

    public static function bonLivraisonService($getShared = true): BonLivraisonService
    {
        if ($getShared) {
            return static::getSharedInstance('bonLivraisonService');
        }

        return new BonLivraisonService();
    }

    public static function produitService($getShared = true): ProduitService
    {
        if ($getShared) {
            return static::getSharedInstance('produitService');
        }
        return new ProduitService();
    }

    public static function demandeClientService($getShared = true): DemandeClientService
    {
        if ($getShared) {
            return static::getSharedInstance('demandeClientService');
        }
        return new DemandeClientService();
    }

    public static function attachmentService($getShared = true): AttachmentService
    {
        if ($getShared) {
            return static::getSharedInstance('attachmentService');
        }
        return new AttachmentService();
    }

    public static function logTokenHistoryUseCase($getShared = true): LogTokenHistoryUseCase
    {
        if ($getShared) {
            return static::getSharedInstance('logTokenHistoryUseCase');
        }

        return new LogTokenHistoryUseCase(
            new TokenHistoryRepository()
        );
    }

    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */
}
