<?php

namespace PostparcBundle\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Cocur\Slugify\Slugify;

class GroupRepository extends NestedTreeRepository
{
    public function search($filterData, $entityId = null, $show_SharedContents = true)
    {
        $dql = $this->createQueryBuilder('g')
            ->select('g.id, g.level, g.name, g.root, g.lft, g.rgt, g.slug, u.id as creatorId, u.username, entity.id as entityId')
            ->leftJoin('g.createdBy', 'u')
            ->leftJoin('g.entity', 'entity')
            ->orderBy('g.root, g.lft', 'ASC')
            ->andWhere('g.deletedAt IS NULL');

        if (array_key_exists('name', $filterData) && $filterData['name']) {
            $dql->andwhere("g.name LIKE '%" . $filterData['name'] . "%'");
        }
        if ($entityId) {
            $where = 'entity.id=' . $entityId;
            if (!(array_key_exists('onlyMyEntityGroups', $filterData) && $filterData['onlyMyEntityGroups']) && $show_SharedContents) {
                $where .= ' OR (entity.id!=' . $entityId . ' AND g.isShared=1)';
            }
            $dql->andwhere($where);
        }
        if (array_key_exists('currentUser', $filterData) ) {
            $currentUser = $filterData['currentUser'];            
            $dql->andWhere('(g.isPrivate!=1 OR (g.isPrivate=1 AND u.id='.$currentUser->getId().'))');
        }

        return $this->_em->createQuery($dql);
    }

    public function batchDelete($ids = null, $entityId = null, $currentUser = null)
    {
        if ($ids) {
            // first query for hard delete
            $hardDql = $this->createQueryBuilder('g')->delete('PostparcBundle\Entity\Group  g')->where('g.id IN (' . implode(',', $ids) . ')');
            $hardDql->andWhere('g.deletedAt IS NOT NULL');

            // second query for soft delete
            $now = new \Datetime();
            $softDql = $this->createQueryBuilder('g')->update('PostparcBundle\Entity\Group g')
              ->set('g.deletedAt', "'" . $now->format('Y-m-d H:i:s') . "'")
              ->where('g.id IN (' . implode(',', $ids) . ')');

            if ($entityId) {
                $hardDql->andWhere('g.entity=' . $entityId);
                $softDql->andWhere('g.entity=' . $entityId);
            }

            if ($currentUser) {
                $softDql->set('g.deletedBy', $currentUser->getId());
            }

            //queries execution
            $this->_em->createQuery($hardDql)->execute();
            $this->_em->createQuery($softDql)->execute();
        }
    }

    public function batchRestore($ids = null, $entityId = null)
    {
        if ($ids) {
            $dql = $this->createQueryBuilder('g')->update('PostparcBundle\Entity\Group  g')
              ->set('g.deletedAt', 'NULL')
              ->set('g.deletedBy', 'NULL')
              ->where('g.id IN (' . implode(',', $ids) . ')');

            if ($entityId) {
                $dql->andWhere('g.entity=' . $entityId);
            }
            $this->_em->createQuery($dql)->execute();
        }
    }

    public function getTrashedElements($entityId = null)
    {
        $dql = $this->createQueryBuilder('g')->select('g')
            ->where('g.deletedAt IS NOT NULL')
            ->orderBy('g.slug', 'ASC');
        if ($entityId) {
            $dql->andWhere('g.entity=' . $entityId);
        }
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    /**
     * methode permettant de recupérer les groupes pouvant être associés à une persone ou un pfo.
     */
    public function getGroupsForSelect($currentUser, $personId = null, $pfoId = null, $organizationId = null, $entityId = null, $show_SharedContents = true)
    {
        $dql = $this->createQueryBuilder('g')
            ->select('g')
            ->orderBy('g.root, g.lft', 'ASC')
            ->where('g.deletedAt IS NULL');
        if ($personId) {
            $dql2 = $this->createQueryBuilder('g')
              ->select('g.id')
              ->leftJoin('g.pfoPersonGroups', 'ppg')
              ->leftJoin('ppg.person', 'p')
              ->where('p.id=' . $personId)
              ->andWhere('g.deletedAt IS NULL');
            if ($entityId && $show_SharedContents) {
                $dql2->leftJoin('g.entity', 'entity')
                ->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND g.isShared=1)')
                ;
            }
            $AlreadyAssociateGroupIds = array_column($this->_em->createQuery($dql2)->getScalarResult(), 'id');
            if (($AlreadyAssociateGroupIds !== []) > 0) {
                $dql->andwhere('g.id not in (' . implode(',', $AlreadyAssociateGroupIds) . ')');
            }
        }
        if ($pfoId) {
            $dql2 = $this->createQueryBuilder('g')
              ->select('g.id')
              ->leftJoin('g.pfoPersonGroups', 'ppg')
              ->leftJoin('ppg.pfo', 'pfo')
              ->where('pfo.id=' . $pfoId)
              ->andWhere('g.deletedAt IS NULL');
            if ($entityId && $show_SharedContents) {
                $dql2->leftJoin('g.entity', 'entity')
                ->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND g.isShared=1)')
                ;
            }
            $AlreadyAssociateGroupIds = array_column($this->_em->createQuery($dql2)->getScalarResult(), 'id');
            if (($AlreadyAssociateGroupIds !== []) > 0) {
                $dql->andwhere('g.id not in (' . implode(',', $AlreadyAssociateGroupIds) . ')');
            }
        }
        if ($organizationId) {
            $dql2 = $this->createQueryBuilder('g')
              ->select('g.id')
              ->leftJoin('g.organizations', 'o')              
              ->where('o.id=' . $organizationId)
              ->andWhere('g.deletedAt IS NULL');
            if ($entityId && $show_SharedContents) {
                $dql2->leftJoin('g.entity', 'entity')
                ->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND g.isShared=1)')
                ;
            }
            $AlreadyAssociateGroupIds = array_column($this->_em->createQuery($dql2)->getScalarResult(), 'id');
            if (($AlreadyAssociateGroupIds !== []) > 0) {
                $dql->andwhere('g.id not in (' . implode(',', $AlreadyAssociateGroupIds) . ')');
            }
        }
        if ($entityId) {
            $where = 'entity.id=' . $entityId;
            if ($show_SharedContents) {
                $where .= ' OR (entity.id!=' . $entityId . ' AND g.isShared=1)';
            }
            $dql->leftJoin('g.entity', 'entity')
              ->andWhere($where)
            ;
        }
        
        if($currentUser->hasRole('ROLE_USER_PLUS') && !$currentUser->hasRole('ROLE_CONTRIBUTOR')) {
            $dql->andWhere('g.createdBy = '.$currentUser->getId());            
        }
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function autoComplete($q, $entityId = null, $show_SharedContents = true, $page_limit = 30, $page = null, $currentUser = null)
    {
        $dql = $this->createQueryBuilder('g')
            ->where('g.deletedAt IS NULL')
            ->leftJoin('g.createdBy', 'u')    
            ->orderby('g.name', 'ASC')
        ;
        if ($q) {
            $slugify = new Slugify();
            $slug = $slugify->slugify($q, '-');
            $dql->andwhere('g.slug LIKE \'%' . $slug . '%\'');
            //$dql->andWhere("g.name LIKE '%".$q."%'");
        }
        if ($entityId) {
            $where = 'entity.id=' . $entityId;
            if ($show_SharedContents) {
                $where .= ' OR (entity.id!=' . $entityId . ' AND g.isShared=1)';
            }
            $dql->leftJoin('g.entity', 'entity')
              ->andWhere($where)
            ;
        }        
        if ($currentUser) {          
            $dql->andWhere('(g.isPrivate!=1 OR (g.isPrivate=1 AND u.id='.$currentUser->getId().'))');
        }
        
        $query = $this->_em->createQuery($dql);
        $query->setMaxResults($page_limit);
        if ($page) {
            $query->setFirstResult(($page - 1) * $page_limit);
        }

        return $query->getResult();
    }

    public function getKeyPair($entityId = null)
    {
        $sql = "SELECT g.id, g.name FROM groups g WHERE g.name <> '' AND deletedAt IS NULL";
        if ($entityId) {
            $sql .= ' AND (g.entity_id=' . $entityId . ' OR (g.entity_id!=' . $entityId . ' AND g.is_shared=1))';
        }
        $sql .= ' ORDER BY g.name';
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
