<?php

namespace PostparcBundle\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Cocur\Slugify\Slugify;

class TerritoryRepository extends NestedTreeRepository
{
    public function search($filter, $entityId = null, $show_SharedContents = true)
    {
        $slugify = new Slugify();
        $dql = $this->createQueryBuilder('t')
            ->select('t.id, t.level, t.name, t.root, t.lft, t.rgt, t.slug, u.id as creatorId, u.username, entity.id as entityId')
            ->where('t.deletedAt IS NULL')
            ->leftJoin('t.createdBy', 'u')
            ->leftJoin('t.territoryType', 'tt')
            ->leftJoin('t.entity', 'entity')
            ->orderBy('t.root, t.lft', 'ASC');

        if (array_key_exists('name', $filter) && $filter['name']) {
            $slug = $slugify->slugify($filter['name'], '-');
            $dql->andwhere('t.slug LIKE \'%' . $slug . '%\'');
        }

        if (array_key_exists('territoryType', $filter) && $filter['territoryType']) {
            $dql->andwhere('tt.id = ' . $filter['territoryType']);
        }

        /*if ($entityId) {
            if ($show_SharedContents) {
                $dql->andWhere('entity.id='.$entityId.' OR (entity.id!='.$entityId.' AND t.isShared=1)');
            } else {
                $dql->andWhere('entity.id='.$entityId.'');
            }
        }*/

        $query = $this->_em->createQuery($dql);

        return $query;
    }

    public function batchDelete($ids = null, $entityId = null, $currentUser = null)
    {
        if ($ids) {
            // first query for hard delete
            $hardDql = $this->createQueryBuilder('t')->delete('PostparcBundle\Entity\Territory t')->where('t.id IN (' . implode(',', $ids) . ')');
            $hardDql->andWhere('t.deletedAt IS NOT NULL');

            // second query for soft delete
            $now = new \Datetime();
            $softDql = $this->createQueryBuilder('t')->update('PostparcBundle\Entity\Territory t')
              ->set('t.deletedAt', "'" . $now->format('Y-m-d H:i:s') . "'")
              ->where('t.id IN (' . implode(',', $ids) . ')');

//            if ($entityId) {
//                $hardDql->andWhere('t.entity='.$entityId);
//                $softDql->andWhere('t.entity='.$entityId);
//            }

            if ($currentUser) {
                $softDql->set('t.deletedBy', $currentUser->getId());
            }

            //queries execution
            $this->_em->createQuery($hardDql)->execute();
            $this->_em->createQuery($softDql)->execute();
        }
    }

    public function batchRestore($ids = null, $entityId = null)
    {
        if ($ids) {
            $dql = $this->createQueryBuilder('t')->update('PostparcBundle\Entity\Territory t')
              ->set('t.deletedAt', 'NULL')
              ->set('t.deletedBy', 'NULL')
              ->where('t.id IN (' . implode(',', $ids) . ')');

            $this->_em->createQuery($dql)->execute();
        }
    }

    public function getTrashedElements()
    {
        $dql = $this->createQueryBuilder('t')->select('t')
            ->where('t.deletedAt IS NOT NULL')
            ->orderBy('t.slug', 'ASC');

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function autoComplete($q, $page_limit = 30, $page = null)
    {
        $dql = $this->createQueryBuilder('t')
            ->where('t.deletedAt IS NULL')
            ->orderby('t.name', 'ASC')
        ;
        if ($q) {
            $dql->andWhere("t.name LIKE '%" . $q . "%'");
        }

        $query = $this->_em->createQuery($dql);
        $query->setMaxResults($page_limit);
        if ($page) {
            $query->setFirstResult(($page - 1) * $page_limit);
        }

        return $query->getResult();
    }

    public function getKeyPair()
    {
        $sql = "SELECT t.id, t.name FROM territory t WHERE t.name <> '' ANd deletedAt IS NULL";
        $sql .= ' ORDER BY t.name';
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
