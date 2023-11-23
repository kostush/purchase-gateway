<?php

namespace ProBillerNG\PurchaseGateway\UI\Console\Commands;

use Doctrine\ORM\EntityManager;
use Illuminate\Console\Command;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\AddonRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\BundleRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\BusinessGroupRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\SiteRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;

class SeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doctrine:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the db with all needed data for dev';

    /** @var BundleRepository */
    protected $bundleRepository;

    /** @var DoctrineBundleProjectionRepository */
    protected $addonRepository;

    /** @var BusinessGroupRepository */
    protected $businessGroupRepository;

    /** @var SiteRepository */
    protected $siteRepository;

    /** @var EntityManager */
    protected $em;

    /**
     * SeedCommand constructor.
     * @param BundleRepository        $bundleRepository        BundleRepository
     * @param AddonRepository         $addonRepository         AddonRepository
     * @param BusinessGroupRepository $businessGroupRepository Business Group Repository
     * @param SiteRepository          $siteRepository          Site Repository
     */
    public function __construct(
        BundleRepository $bundleRepository,
        AddonRepository $addonRepository,
        BusinessGroupRepository $businessGroupRepository,
        SiteRepository $siteRepository
    ) {
        parent::__construct();
        $this->bundleRepository        = $bundleRepository;
        $this->addonRepository         = $addonRepository;
        $this->businessGroupRepository = $businessGroupRepository;
        $this->siteRepository          = $siteRepository;

        $this->em = app('em');
    }

    /**
     * Handles the command
     * @return void
     * @throws \Throwable
     */
    public function handle(): void
    {
        if (!app()->environment('local')) {
            return;
        }

        $this->em->transactional(
            function () {
                $this->createAddons();
                $this->createBundleAddonRelation();
                $this->createBusinessGroups();
                $this->createSites();
            }
        );
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function createAddons(): void
    {
        $sql  = <<<SQL
INSERT IGNORE INTO `addons` (`addon_id`, `type`) VALUES 
    ('0b45e8b1-78a3-465e-ad4e-becbc1fb1331', 'content'),
	('4e1b0d7e-2956-11e9-b210-d663bd873d93', 'content'),
	('670af402-2956-11e9-b210-d663bd873d93', 'content'),
	('94dd40cf-3de3-4c5b-a214-1cf6bd580683', 'content'),
	('d1bf20b4-8e19-4f5b-9598-9dfa0987dd67', 'content'),
	('d718aafa-908b-42af-a559-4afa79095eff', 'content'),
    ('b2c9d4b2-a2ac-40d7-a036-3303bc0c8f08','content'),
    ('d1039649-f469-4333-943c-057c437ff244','content');
SQL;
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function createBundleAddonRelation(): void
    {
        $sql  = <<<SQL
INSERT IGNORE INTO `bundles` (`bundle_id`, `addon_id`, `addon_type`, `require_active_content`) VALUES
	('a0a3aa08-f106-410d-ae6e-34f92d98f09b', '94dd40cf-3de3-4c5b-a214-1cf6bd580683', 'content', 1),
	('4475820e-2956-11e9-b210-d663bd873d93', '4e1b0d7e-2956-11e9-b210-d663bd873d93', 'content', 1),
	('ee9279bb-ccc2-499b-86ca-47c60e0751bf', 'd1bf20b4-8e19-4f5b-9598-9dfa0987dd67', 'content', 1),
	('c757e101-cb74-4161-b524-33ba2f288d41', '0b45e8b1-78a3-465e-ad4e-becbc1fb1331', 'content', 1),
	('5fd44440-2956-11e9-b210-d663bd873d93', '670af402-2956-11e9-b210-d663bd873d93', 'content', 1),
	('2c7bba13-0fca-47f7-aa23-b0e23fc60611', 'd718aafa-908b-42af-a559-4afa79095eff', 'content', 1),
    ('1e88a523-d635-432d-a100-d0d02bbc4205', 'd1039649-f469-4333-943c-057c437ff244', 'content', 0),
    ('fd102a7e-b496-4d69-a796-7c927675b94f', 'b2c9d4b2-a2ac-40d7-a036-3303bc0c8f08', 'content', 0);
SQL;
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function createBusinessGroups(): void
    {
        $sql  = <<<SQL
INSERT IGNORE INTO `business_groups` VALUES 
    ('00e89c65-0748-4695-934d-330afb86e075','[{\"key\":\"1b1413b4-3381-11e9-b210-d663bd873d93\",\"createdAt\":\"2019-07-26T14:47:28+00:00\"}]','1fc95fcc-3381-11e9-b210-d663bd873d93',''),
    ('07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1','[{\"key\":\"9276b15e-258d-4bfe-8bd9-74f25dc3f918\",\"createdAt\":\"2019-07-26T14:47:28+00:00\"}]','123e4567-e89b-12d3-a456-426655440015','MBI*PROBILLER.COM'),
    ('db89507e-141c-11ea-8d71-362b9e155667','[{\"key\":\"c36aba22-0134-47ce-b030-b909466ffcc9\",\"createdAt\":\"2019-12-06T09:38:51+00:00\"}]','71ffdebf-59c4-44ec-aff9-196f2cae9aee',''),
    ('db8952f4-141c-11ea-8d71-362b9e155667','[{\"key\":\"9856ef03-e0d8-48d5-be0d-dc230baef36e\",\"createdAt\":\"2019-12-06T09:39:24+00:00\"}]','c96621d4-479d-4598-9566-8845869b0c41','');
SQL;
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function createSites(): void
    {
        $sql  = <<<SQL
INSERT IGNORE INTO `sites` VALUES 
    (1,'8e34c94e-135f-4acb-9141-58b3a6e56c74','07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1','http://www.realitykings.com','Reality Kings','1-877-271-6423','https://is.gd/A3w2Db','https://support.realitykings.com','welcome@probiller.com','http://supportchat.contentabc.com/?domain=probiller.com/rk','https://stage1-payment-gateway.project1service.com/v2/purchase-postback','[{\"name\":\"fraud\",\"enabled\":true},{\"name\":\"bin-routing\",\"enabled\":true},{\"name\":\"email-service\",\"enabled\":true,\"options\":{\"templateId\":\"eeed8906-4c34-4ea8-89ee-445f3291b1a3\",\"senderName\":\"Probiller\",\"senderEmail\":\"welcome@probiller.com\"}}]','123e4567-e89b-12d3-a456-426655440015','[{\"key\":\"9276b15e-258d-4bfe-8bd9-74f25dc3f918\",\"createdAt\":{\"date\":\"2019-07-26 14:47:28.000000\",\"timezone_type\":1,\"timezone\":\"+00:00\"}}]','MBI*PROBILLER.COM',0,1),
    (2,'4c22fba2-f883-11e8-8eb2-f2801f1b9fd1','07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1','http://realitykingspremium.com','Reality Kings Premium','1-877-271-6423','https://is.gd/A3w2Db','https://support.realitykings.com','welcome@probiller.com','http://supportchat.contentabc.com/?domain=probiller.com/rk','','[{\"name\":\"fraud\",\"enabled\":true},{\"name\":\"bin-routing\",\"enabled\":true},{\"name\":\"email-service\",\"enabled\":true,\"options\":{\"templateId\":\"eeed8906-4c34-4ea8-89ee-445f3291b1a3\",\"senderName\":\"Probiller\",\"senderEmail\":\"welcome@probiller.com\"}}]','123e4567-e89b-12d3-a456-426655440015','[{\"key\":\"9276b15e-258d-4bfe-8bd9-74f25dc3f918\",\"createdAt\":{\"date\":\"2019-07-26 14:47:28.000000\",\"timezone_type\":1,\"timezone\":\"+00:00\"}}]','MBI*PROBILLER.COM', 0,0),
    (3,'6128e740-2a61-43e0-b717-c9976b4ec3c5','07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1','http://www.marketplace.com','Market Place','1-855-232-9555','https://is.gd/OUTNYP','https://support.marketplace.com','welcome@probiller.com','http://supportchat.contentabc.com/?domain=probiller.com/marketplace','https://stage1-payment-gateway.project1service.com/v2/purchase-postback','[{\"name\":\"fraud\",\"enabled\":true},{\"name\":\"bin-routing\",\"enabled\":true},{\"name\":\"email-service\",\"enabled\":true,\"options\":{\"templateId\":\"eeed8906-4c34-4ea8-89ee-445f3291b1a3\",\"senderName\":\"Probiller\",\"senderEmail\":\"welcome@probiller.com\"}}]','123e4567-e89b-12d3-a456-426655440015','[{\"key\":\"9276b15e-258d-4bfe-8bd9-74f25dc3f918\",\"createdAt\":{\"date\":\"2019-07-26 14:47:28.000000\",\"timezone_type\":1,\"timezone\":\"+00:00\"}}]','MBI*PROBILLER.COM',0,0),
    (4,'299d3e6b-cf3d-11e9-8c91-0cc47a283dd2','07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1','http://www.brazzers.com','Brazzers','1-855-232-9555','https://is.gd/HathAW','https://support.brazzers.com','brazzers@probiller.com','http://supportchat.contentabc.com/?domain=brazzerssupport.com','https://stage1-payment-gateway.project1service.com/v2/purchase-postback','[{\"name\":\"fraud\",\"enabled\":false},{\"name\":\"bin-routing\",\"enabled\":true},{\"name\":\"email-service\",\"enabled\":true,\"options\":{\"templateId\":\"eeed8906-4c34-4ea8-89ee-445f3291b1a3\",\"senderName\":\"Probiller\",\"senderEmail\":\"welcome@probiller.com\"}}]','123e4567-e89b-12d3-a456-426655440015','[{\"key\":\"9276b15e-258d-4bfe-8bd9-74f25dc3f918\",\"createdAt\":{\"date\":\"2019-07-26 14:47:28.000000\",\"timezone_type\":1,\"timezone\":\"+00:00\"}}]','MBI*PROBILLER.COM',0,0),
    (5,'29a1ee81-cf3d-11e9-8c91-0cc47a283dd2','07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1','http://www.brazzerspremium.com','Brazzers Premium','1-855-232-9555','https://is.gd/HathAW','https://support.brazzers.com','brazzers@probiller.com','http://supportchat.contentabc.com/?domain=brazzerssupport.com','https://stage1-payment-gateway.project1service.com/v2/purchase-postback','[{\"name\":\"fraud\",\"enabled\":false},{\"name\":\"bin-routing\",\"enabled\":true},{\"name\":\"email-service\",\"enabled\":true,\"options\":{\"templateId\":\"eeed8906-4c34-4ea8-89ee-445f3291b1a3\",\"senderName\":\"Probiller\",\"senderEmail\":\"welcome@probiller.com\"}}]','123e4567-e89b-12d3-a456-426655440015','[{\"key\":\"9276b15e-258d-4bfe-8bd9-74f25dc3f918\",\"createdAt\":{\"date\":\"2019-07-26 14:47:28.000000\",\"timezone_type\":1,\"timezone\":\"+00:00\"}}]','MBI*PROBILLER.COM',0,0),
    (6,'29a4e719-cf3d-11e9-8c91-0cc47a283dd2','07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1','http://premium.pornportal.com','Premium Pornportal','1-877-467-1692','https://is.gd/ggbpnH','https://support.pornportal.com','https://support.pornportal.com/email/','http://supportchat.contentabc.com/?domain=support.pornportal.com','https://stage1-payment-gateway.project1service.com/v2/purchase-postback','[{\"name\":\"fraud\",\"enabled\":true},{\"name\":\"bin-routing\",\"enabled\":true},{\"name\":\"email-service\",\"enabled\":true,\"options\":{\"templateId\":\"eeed8906-4c34-4ea8-89ee-445f3291b1a3\",\"senderName\":\"Probiller\",\"senderEmail\":\"welcome@probiller.com\"}}]','123e4567-e89b-12d3-a456-426655440015','[{\"key\":\"9276b15e-258d-4bfe-8bd9-74f25dc3f918\",\"createdAt\":{\"date\":\"2019-07-26 14:47:28.000000\",\"timezone_type\":1,\"timezone\":\"+00:00\"}}]','MBI*PROBILLER.COM',0,0),
    (7,'e34ca48c-3380-11e9-b210-d663bd873d93','00e89c65-0748-4695-934d-330afb86e075','','','','','','','','https://stage1-payment-gateway.project1service.com/v2/purchase-postback','[]','1fc95fcc-3381-11e9-b210-d663bd873d93','[{\"key\":\"1b1413b4-3381-11e9-b210-d663bd873d93\",\"createdAt\":{\"date\":\"2019-07-26 14:47:28.000000\",\"timezone_type\":1,\"timezone\":\"+00:00\"}}]','',0,0),
    (8,'299b14d0-cf3d-11e9-8c91-0cc47a283dd2','db89507e-141c-11ea-8d71-362b9e155667','http://www.pornhubpremium.com','Pornhub Premium','1-855-232-9555','https://is.gd/OUTNYP','https://support.pornhubpremium.com','welcome@probiller.com','http://supportchat.contentabc.com/?domain=probiller.com/pornhubpremium','https://stage1-payment-gateway.project1service.com/v2/purchase-postback','[{\"name\":\"fraud\",\"enabled\":true},{\"name\":\"bin-routing\",\"enabled\":true},{\"name\":\"email-service\",\"enabled\":true,\"options\":{\"templateId\":\"eeed8906-4c34-4ea8-89ee-445f3291b1a3\",\"senderName\":\"Probiller\",\"senderEmail\":\"welcome@probiller.com\"}}]','71ffdebf-59c4-44ec-aff9-196f2cae9aee','[{\"key\":\"c36aba22-0134-47ce-b030-b909466ffcc9\",\"createdAt\":{\"date\":\"2019-07-26 14:47:28.000000\",\"timezone_type\":1,\"timezone\":\"+00:00\"}}]','MBI*PROBILLER.COM',0,0),
    (9,'299f9d47-cf3d-11e9-8c91-0cc47a283dd2','07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1','http://www.men.com','Men','877-695-0685','https://is.gd/IsZTWX','https://support.men.com/','men@probiller.com','https://support.men.com/','https://payment-gateway.project1service.com/v2/purchase-postback','[{"name":"bin-routing","enabled":true},{"name":"email-service","enabled":true},{"name":"fraud","enabled":true}]','123e4567-e89b-12d3-a456-426655440015','[{"key":"9276b15e-258d-4bfe-8bd9-74f25dc3f918","createdAt":{"date":"2020-01-10 16:50:05.000000","timezone_type":1,"timezone":"+00:00"}},{"key":"816539fc-39e4-4a3d-ab38-321e8661c0ac","createdAt":{"date":"2020-04-16 17:21:05.000000","timezone_type":1,"timezone":"+00:00"}}]','PROBILLER.COM 855-232-9555',1,0),
    (10,'c2e52e3b-2d18-4e81-adf2-280c31728373','07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1','http://www.Bromo.com','Bromo','877-213-9768','https://is.gd/ESVj72','https://support.bromo.com/','bromo@probiller.com','https://support.bromo.com/','https://payment-gateway.project1service.com/v2/purchase-postback','[{"name":"bin-routing","enabled":true},{"name":"email-service","enabled":true},{"name":"fraud","enabled":true}]','123e4567-e89b-12d3-a456-426655440015','[{"key":"9276b15e-258d-4bfe-8bd9-74f25dc3f918","createdAt":{"date":"2020-01-10 16:50:05.000000","timezone_type":1,"timezone":"+00:00"}},{"key":"816539fc-39e4-4a3d-ab38-321e8661c0ac","createdAt":{"date":"2020-04-16 17:21:05.000000","timezone_type":1,"timezone":"+00:00"}}]','PROBILLER.COM 855-232-9555',1,0);
SQL;
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
    }
}
