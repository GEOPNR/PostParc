<?php

namespace PostparcBundle\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Cocur\Slugify\Slugify;

class TagRepository extends NestedTreeRepository
{
    public function search($filter)
    {
        $slugify = new Slugify();
        $dql = $this->createQueryBuilder('t')
            ->select('t.id, t.level, t.name, t.root, t.lft, t.rgt, t.slug, u.id as creatorId, u.username')
            ->leftJoin('t.createdBy', 'u')
            ->orderBy('t.root, t.lft', 'ASC');

        if (array_key_exists('name', $filter) && $filter['name']) {
            $slug = $slugify->slugify($filter['name'], '-');
            $dql->andwhere('t.slug LIKE \'%' . $slug . '%\'');
        }

        return $this->_em->createQuery($dql);
    }

    public function batchDelete($ids = null, $entityId = null)
    {
        if ($ids) {
            $dql = $this->createQueryBuilder('t')->delete('PostparcBundle\Entity\Tag t')->where('t.id IN (' . implode(',', $ids) . ')');

            if ($entityId) {
                $dql->andWhere('t.entity=' . $entityId);
            }

            //queries execution
            $this->_em->createQuery($dql)->execute();
        }
    }

    public function autoComplete($q, $page_limit = 30, $page = null)
    {
        $dql = $this->createQueryBuilder('t')
                ->leftJoin('t.parent','p')
                ->leftJoin('t.children','c')
                ->orderby('t.name', 'ASC')
        ;
        if ($q) {
            $slugify = new Slugify();
            $slug = $slugify->slugify($q, '-');
            $dql->andwhere('t.slug LIKE \'%' . $slug . '%\'');
            $dql->orWhere('p.slug LIKE \'%' . $slug . '%\'');
            $dql->orWhere('c.slug LIKE \'%' . $slug . '%\'');
            //$dql->andWhere("t.name LIKE '%".$q."%'");
        }
        $query = $this->_em->createQuery($dql);
        if ($page) {
            $query->setFirstResult(($page - 1) * $page_limit);
        }

        return $query->getResult();
    }

    public function getKeyPair()
    {
        $dql = "SELECT t.id, t.name FROM tag t WHERE t.name <> '' ORDER BY t.name";
        $stmt = $this->getEntityManager()->getConnection()->prepare($dql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
