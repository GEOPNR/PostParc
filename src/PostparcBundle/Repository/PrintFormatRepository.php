<?php

namespace PostparcBundle\Repository;

/**
 * ServiceRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PrintFormatRepository extends \Doctrine\ORM\EntityRepository
{
    public function search($filter, $entityId = null, $show_SharedContents = true)
    {
        //TODO gérer les value vide
        $dql = $this->createQueryBuilder('c')->select('c');
        $dql->andWhere('c.deletedAt IS NULL');

        (array_key_exists('name', $filter) && $filter['name']) ? $dql->andwhere("c.name LIKE '%" . $filter['name'] . "%'") : '';

        if ($entityId) {
            $dql->leftJoin('c.entity', 'entity');
            if ($show_SharedContents) {
                $dql->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND c.isShared=1)');
            } else {
                $dql->andWhere('entity.id=' . $entityId . '');
            }
        }

        return $query = $this->_em->createQuery($dql);
    }

    public function getPrintFormatsForSelect($entityId = null, $show_SharedContents = true)
    {
        $dql = $this->createQueryBuilder('c')->select('c')
                ->andWhere('c.deletedAt IS NULL')
                ->orderBy('c.slug', 'ASC');

        if ($entityId) {
            $dql->leftJoin('c.entity', 'entity');
            if ($show_SharedContents) {
                $dql->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND c.isShared=1)');
            } else {
                $dql->andWhere('entity.id=' . $entityId . '');
            }
        }
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function batchDelete($ids = null, $entityId = null, $currentUser = null)
    {
        if ($ids) {
            // first query for hard delete
            $hardDql = $this->createQueryBuilder('pf')->delete('PostparcBundle\Entity\PrintFormat  pf')->where('pf.id IN (' . implode(',', $ids) . ')');
            $hardDql->andWhere('pf.deletedAt IS NOT NULL');

            // second query for soft delete
            $now = new \Datetime();
            $softDql = $this->createQueryBuilder('pf')->update('PostparcBundle\Entity\PrintFormat pf')
                    ->set('pf.deletedAt', "'" . $now->format('Y-m-d H:i:s') . "'")
                    ->where('pf.id IN (' . implode(',', $ids) . ')');

            if ($entityId) {
                $hardDql->andWhere('pf.entity=' . $entityId);
                $softDql->andWhere('pf.entity=' . $entityId);
            }

            if ($currentUser) {
                $softDql->set('pf.deletedBy', $currentUser->getId());
            }

            //queries execution
            $this->_em->createQuery($hardDql)->execute();
            $this->_em->createQuery($softDql)->execute();
        }
    }

    public function batchRestore($ids = null, $entityId = null)
    {
        if ($ids) {
            $dql = $this->createQueryBuilder('pf')->update('PostparcBundle\Entity\PrintFormat  pf')
                    ->set('pf.deletedAt', 'NULL')
                    ->set('pf.deletedBy', 'NULL')
                    ->where('pf.id IN (' . implode(',', $ids) . ')');

            if ($entityId) {
                $dql->andWhere('pf.entity=' . $entityId);
            }
            $this->_em->createQuery($dql)->execute();
        }
    }

    public function getTrashedElements($entityId = null)
    {
        $dql = $this->createQueryBuilder('pf')->select('pf')
                ->where('pf.deletedAt IS NOT NULL')
                ->orderBy('pf.slug', 'ASC');
        if ($entityId) {
            $dql->andWhere('pf.entity=' . $entityId);
        }
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }
}
