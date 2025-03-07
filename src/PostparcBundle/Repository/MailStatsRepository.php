<?php

namespace PostparcBundle\Repository;

/**
 * MailStatsRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MailStatsRepository extends \Doctrine\ORM\EntityRepository
{
    public function getComsuptionForCurrentMonth($entityID = null)
    {
        $now = new \DateTime();
        $dql = $this->createQueryBuilder('ms')
            ->select('SUM(ms.nbEmail) as nbEmail, SUM(ms.attachmentsSize) as attachmentsSize')
            ->where('ms.date LIKE \'' . $now->format('Y-m') . '%\'')
        ;
        if ($entityID) {
            $dql->leftJoin('ms.createdBy', 'u')
                ->leftJoin('u.entity', 'e')
                ->andWhere('e.id=' . $entityID);
        }
        $query = $this->_em->createQuery($dql);

        return  $query->getOneOrNullResult();
    }

    public function getStatsByMonth($currentUser)
    {
        $entityID = null;
        if (!$currentUser->hasRole('ROLE_SUPER_ADMIN')) {
            $entityID = $currentUser->getEntity()->getId();
        }
        $dql = "SELECT substring_index(sender, '@', -1) as sender, MONTH(date) AS Month ,YEAR(date) as Year , SUM( nbEmail ) AS nbEmail, SUM( attachmentsSize ) AS attachmentsSize
            FROM  `mail_stats` ms
            ";
        $whereSeparator = ' WHERE ';
        if ($entityID) {
            $dql .= ' LEFT JOIN `fos_user` fu ON ms.created_by_id=fu.id'
                    . $whereSeparator . ' fu.entity_id=' . $entityID;
            $whereSeparator = ' AND ';
            if (!$currentUser->hasRole('ROLE_ADMIN')) {
                $dql .= $whereSeparator. ' ms.created_by_id='.$currentUser->getId();
            }
        }
        // limitation periode
        $date = new \DateTime();
        $date->sub(new \DateInterval('P1Y'));
        //$dql .= $whereSeparator.' ms.date >= "'.$date->format('Y-m-d').'"';
        $dql .= $whereSeparator . ' ms.date >= "' . $date->format('Y') . '-01-01"';

        $dql .= " GROUP BY  YEAR(date) , MONTH(date), substring_index(sender, '@', -1)";

        $q = $this->getEntityManager()->getConnection()->prepare($dql);
        $q->execute();
        $result = $q->fetchAll();

        $tab = [];

        foreach ($result as $value) {
            $tab[$value['Year']][$value['Month']][$value['sender']] = (int) $value['nbEmail'];
        }

        return $tab;
    }

    public function getDetailedStats($currentUser)
    {
        $entityID = null;
        if (!$currentUser->hasRole('ROLE_SUPER_ADMIN')) {
            $entityID = $currentUser->getEntity()->getId();
        }
        $dql = $this->createQueryBuilder('ms')
        ->select('ms, u')
        ->leftJoin('ms.createdBy', 'u')
        ->orderBy('ms.date', 'DESC');
        if ($entityID) {
            $dql->leftJoin('u.entity', 'e')
                ->where('e.id=' . $entityID);
            if (!$currentUser->hasRole('ROLE_ADMIN')) {
                $dql->andWhere('u.id='.$currentUser->getId());
            }
        }
        // limitation periode
        $date = new \DateTime();
        $date->sub(new \DateInterval('P6M'));
        $dql->andWhere('ms.date >= \''.$date->format('Y-m-d').'\'');
        //$dql->andWhere('ms.date >= \'' . $date->format('Y') . '-01-01\'');

        return  $this->_em->createQuery($dql);
    }
}
