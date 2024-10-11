<?php

namespace PostparcBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PfoPersonGroupRepository.
 */
class PfoPersonGroupRepository extends EntityRepository
{
    public function listPersonnGroup($groupId)
    {
        return $this->listPersonnGroupQuery($groupId)->getResult();
    }

    public function listPersonnGroupQuery($groupId)
    {
        $dql = $this->createQueryBuilder('ppg')
                ->select('ppg, p, p2, pfo, g, f, af, o')
                ->leftJoin('ppg.person', 'p')
                ->leftJoin('ppg.pfo', 'pfo')
                ->leftJoin('pfo.person', 'p2')
                ->leftJoin('pfo.personFunction', 'f')
                ->leftJoin('pfo.additionalFunction', 'af')
                ->leftJoin('pfo.service', 's')
                ->leftJoin('pfo.organization', 'o')
                ->leftJoin('ppg.group', 'g')
                ->andwhere('g.id=' . $groupId)
                ->andWhere('pfo.deletedAt IS NULL')
                ->andWhere('p.deletedAt IS NULL')
                ->andWhere('p2.deletedAt IS NULL')
        ;

        return $this->_em->createQuery($dql);
    }

    public function listPersonnSubGroup($childrens)
    {
        return $this->listPersonnSubGroupQuery($childrens)->getResult();
    }

    public function listPersonnSubGroupQuery($childrens)
    {
        $dql = $this->createQueryBuilder('ppg')
                ->select('ppg, p, p2, pfo, g, f, af, o')
                ->leftJoin('ppg.person', 'p')
                ->leftJoin('ppg.pfo', 'pfo')
                ->leftJoin('pfo.person', 'p2')
                ->leftJoin('pfo.personFunction', 'f')
                ->leftJoin('pfo.additionalFunction', 'af')
                ->leftJoin('pfo.service', 's')
                ->leftJoin('pfo.organization', 'o')
                ->leftJoin('ppg.group', 'g')
                ->andWhere('pfo.deletedAt IS NULL')
                ->andWhere('p.deletedAt IS NULL')
                ->andWhere('p2.deletedAt IS NULL')
        ;
        $groupId = [];
        foreach ($childrens as $group) {
            $groupId[] = $group->getId();
        }
        $dql->andWhere('g.id IN (' . implode(',', $groupId) . ')');
        $dql->andWhere('g.deletedAt IS NULL');

        return $this->_em->createQuery($dql);
    }

    public function batchDelete($ids = null)
    {
        if ($ids) {
            $dql = $this->createQueryBuilder('ppg')->delete('PostparcBundle\Entity\PfoPersonGroup  ppg')->where('ppg.id IN (' . implode(',', $ids) . ')');

            return $query = $this->_em->createQuery($dql)->execute();
        }
    }
}
