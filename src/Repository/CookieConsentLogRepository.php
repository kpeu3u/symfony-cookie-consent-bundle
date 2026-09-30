<?php

declare(strict_types=1);

namespace CookieConsentBundle\Repository;

use CookieConsentBundle\Entity\CookieConsentLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CookieConsentLog> */
class CookieConsentLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CookieConsentLog::class);
    }
}
