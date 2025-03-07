<?php

namespace PostparcBundle\Repository;

use Cocur\Slugify\Slugify;

/**
 * ServiceRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrganizationRepository extends \Doctrine\ORM\EntityRepository
{
    public function search($filter, $entityId = null, $readerLimitations = null, $show_SharedContents = true)
    {
        $slugify = new Slugify();
        $dql = $this->createQueryBuilder('o')->select('o')
            ->select('o, co, ot, rep, evt, ety, city, email')
            ->leftJoin('o.coordinate', 'co')
            ->leftJoin('co.email', 'email')
            ->leftJoin('co.city', 'city')
            ->leftJoin('o.entity', 'ety')
            ->leftJoin('o.organizationType', 'ot')
            ->leftJoin('co.representation', 'rep')
            ->leftJoin('co.event', 'evt')
            ->where('o.deletedAt IS NULL')
        ;

        if (array_key_exists('name', $filter) && $filter['name']) {
            $slug = $slugify->slugify(str_replace('.', '-', $filter['name']), '-');
            $dql->andwhere('o.slug LIKE \'%' . $slug . '%\'');
        }
        if (isset($filter['updatedBy']) && $filter['updatedBy']) {
            $dql->andwhere("o.updatedBy = '" . $filter['updatedBy'] . "'");
        }
        if (isset($filter['organizationType']) && $filter['organizationType']) {
            if (isset($filter['organizationTypeIds']) && count($filter['organizationTypeIds'])) {
                // recherche dans les sous organizationType
                $dql->andwhere("o.organizationType IN (" . implode(',', $filter['organizationTypeIds']) . ")");
            } else {
                $dql->andwhere("o.organizationType = '" . $filter['organizationType'] . "'");
            }
        }
        if (isset($filter['abbreviation']) && strlen($filter['abbreviation'])) {
            $dql->andwhere('o.abbreviation LIKE \'%' . str_replace("'", '%', str_replace('.', '', $filter['abbreviation'])) . '%\'');
        }
        if (isset($filter['siret']) && strlen($filter['siret'])) {
            $dql->andwhere('o.siret LIKE \'%' . str_replace("'", '%', str_replace('.', '', $filter['siret'])) . '%\'');
        }
        if (array_key_exists('tags', $filter) && $filter['tags']) {
            $tagIds = [];
            foreach ($filter['tags'] as $tag) {
                $tagIds[] = $tag->getId();
                // search in sub tags
                $subTags = $this->getEntityManager()->getRepository('PostparcBundle\Entity\Tag')->getChildren($node = $tag, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                foreach ($subTags as $subTag) {
                    $tagIds[] = $subTag->getId();
                }
            }
            if (($tagIds !== []) > 0) {
                $dql->leftJoin('o.tags', 't');
                $dql->andWhere('t.id IN (' . implode(',', $tagIds) . ')');
            }
        }
        if (isset($filter['city']) && $filter['city']) {
            $dql->andWhere('city.id=' . $filter['city']);
        }

        if ($entityId) {
            $dql->leftJoin('o.entity', 'entity');
            if ($show_SharedContents) {
                $dql->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND o.isShared=1)');
            } else {
                $dql->andWhere('entity.id=' . $entityId . '');
            }
        }

        if (is_array($readerLimitations)) {
            if (array_key_exists('organizationTypeIds', $readerLimitations) && is_array($readerLimitations['organizationTypeIds']) && count($readerLimitations['organizationTypeIds']) && 'off' == $readerLimitations['organizationType_noLimitation']) {
                $dql->andwhere('ot.id IS NULL OR ot.id  IN (' . implode(',', $readerLimitations['organizationTypeIds']) . ')');
            }
            if (array_key_exists('tagIds', $readerLimitations) && is_array($readerLimitations['tagIds']) && count($readerLimitations['tagIds']) && 'off' == $readerLimitations['tag_noLimitation']) {
                $dql->leftJoin('o.tags', 'tag');
                $dql->andwhere('tag.id IS NULL OR tag.id  IN (' . implode(',', $readerLimitations['tagIds']) . ')');
            }
        }

        (array_key_exists('orderBy', $filter)) ? $dql->orderBy($filter['orderBy']['field'], $filter['orderBy']['direction']) : '';

        return $query = $this->_em->createQuery($dql);
    }

    public function getOrganization($id)
    {
        $dql = $this->createQueryBuilder('o')->select('o')
            ->leftJoin('o.coordinate', 'co')
            ->leftJoin('o.pfos', 'pfos')
            ->leftJoin('pfos.person', 'p')
            ->leftJoin('pfos.personFunction', 'pf')
            ->leftJoin('o.organizationType', 'ot')
            ->where('o.id = :id')
            ->andWhere('o.deletedAt IS NULL')
        ;

        return $query = $this->_em->createQuery($dql)->setParameter('id', $id)->getOneOrNullResult();
    }

    public function batchDelete($ids = null, $entityId = null, $currentUser = null)
    {
        if ($ids) {
            // first query for hard delete
            $hardDql = $this->createQueryBuilder('o')->delete('PostparcBundle\Entity\Organization o')->where('o.id IN (' . implode(',', $ids) . ')');
            $hardDql->andWhere('o.deletedAt IS NOT NULL');
            // suppression des coordonnées associées
            $entitiesToBeDeleteDql = $this->createQueryBuilder('o')->select('o')->where('o.id IN (' . implode(',', $ids) . ')')->andWhere('o.deletedAt IS NOT NULL');

            // second query for soft delete
            $now = new \Datetime();
            $softDql = $this->createQueryBuilder('o')->update('PostparcBundle\Entity\Organization o')
              ->set('o.deletedAt', "'" . $now->format('Y-m-d H:i:s') . "'")
              ->where('o.id IN (' . implode(',', $ids) . ')');

            if ($entityId) {
                $hardDql->andWhere('o.entity=' . $entityId);
                $softDql->andWhere('o.entity=' . $entityId);
                $entitiesToBeDeleteDql->andWhere('o.entity=' . $entityId);
            }
            // suppression des coordonnées associées
            $entitiesToBeDelete = $this->_em->createQuery($entitiesToBeDeleteDql)->getResult();
            $havetoBeFlush = false;
            foreach ($entitiesToBeDelete as $entity) {
                $coordinate = $entity->getCoordinate();
                if ($coordinate) {
                    $email = $coordinate->getEmail();
                    $this->_em->remove($coordinate);
                    if ($email) {
                        $this->_em->remove($email);
                    }
                }
                $havetoBeFlush = true;
            }
            if ($havetoBeFlush) {
                $this->_em->flush();
            }

            if ($currentUser) {
                $softDql->set('o.deletedBy', $currentUser->getId());
            }

            //queries execution
            $this->_em->createQuery($hardDql)->execute();
            $this->_em->createQuery($softDql)->execute();
        }
    }

    public function organizationIdsInGroup($groupId)
    {
        $dql = $this->createQueryBuilder('o')
            ->select('o.id')
            ->leftjoin('o.groups', 'g')
            ->where('g.id=' . $groupId)
            ->andWhere('o.deletedAt IS NULL');
        $query = $this->_em->createQuery($dql);
        $result = $query->getScalarResult();

        return array_map('current', $result);
    }

    public function listOrganizationGroupQuery($groupId)
    {
        $dql = $this->createQueryBuilder('o')
            ->leftJoin('o.groups', 'g')
            ->where('g.id=' . $groupId)
            ->andWhere('o.deletedAt IS NULL')
        ;

        return $this->_em->createQuery($dql);
    }

    public function listOrganizationSubGroupQuery($childrens)
    {
        $dql = $this->createQueryBuilder('o')
            ->where('o.deletedAt IS NULL')
            ->leftJoin('o.groups', 'g')
        ;
        $groupId = [];
        foreach ($childrens as $group) {
            $groupId[] = $group->getId();
        }
        $dql->andWhere('g.id IN (' . implode(',', $groupId) . ')');

        return $this->_em->createQuery($dql);
    }

    public function batchRestore($ids = null, $entityId = null)
    {
        if ($ids) {
            $dql = $this->createQueryBuilder('p')->update('PostparcBundle\Entity\Organization o')
              ->set('o.deletedAt', 'NULL')
              ->set('o.deletedBy', 'NULL')
              ->where('o.id IN (' . implode(',', $ids) . ')');

            if ($entityId) {
                $dql->andWhere('o.entity=' . $entityId);
            }
            $this->_em->createQuery($dql)->execute();
        }
    }

    public function getTrashedElements($entityId = null)
    {
        $dql = $this->createQueryBuilder('o')->select('o')
            ->where('o.deletedAt IS NOT NULL')
            ->orderBy('o.slug', 'ASC');
        if ($entityId) {
            $dql->andWhere('o.entity=' . $entityId);
        }
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function autoComplete($q, $entityId = null, $show_SharedContents = true, $page_limit = 60, $page = null)
    {
        $dql = $this->createQueryBuilder('o')
            ->orderby('o.slug', 'ASC')
            ->where('o.deletedAt IS NULL')
        ;

        if ($q) {
            $slugify = new Slugify();
            $slug = $slugify->slugify(str_replace("'", "", $q), '-');
            $dql->andwhere("o.slug LIKE '%" . $slug . "%' OR o.abbreviation LIKE '%" . str_replace("'", '_', $q) . "%'");
        }
        if ($entityId) {
            $dql->leftJoin('o.entity', 'entity');
            if ($show_SharedContents) {
                $dql->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND o.isShared=1)');
            } else {
                $dql->andWhere('entity.id=' . $entityId . '');
            }
        }
        $query = $this->_em->createQuery($dql);
        $query->setMaxResults($page_limit);
        if ($page) {
            $query->setFirstResult(($page - 1) * $page_limit);
        }

        return $query->getResult();
    }

    public function simpleSearch($q, $entityId = null, $readerLimitations = null, $show_SharedContents = true)
    {
        $dql = $this->createQueryBuilder('o')
            ->select('o, ot, coord, city, e, rep, evt, ety')
            ->leftJoin('o.coordinate', 'coord')
            ->leftJoin('coord.representation', 'rep')
            ->leftJoin('coord.event', 'evt')
            ->leftJoin('o.entity', 'ety')
            ->leftJoin('coord.email', 'e')
            ->leftJoin('o.organizationType', 'ot')
            ->leftJoin('coord.city', 'city')
            ->orderby('o.name', 'ASC')
            ->where('o.deletedAt IS NULL')
        ;
        if ($q) {
            $slugify = new Slugify();
            $q = str_replace("'", '', $q);
            $slug = $slugify->slugify($q, '-');
            $whereCondition = "o.name LIKE '%" . $q . "%' "
              . " OR o.abbreviation LIKE '%" . $q . "%' "
              . " OR o.siret LIKE '%" . $q . "%' "      
              . " OR ot.name LIKE '%" . $q . "%' "
              . " OR o.slug LIKE '%" . $slug . "%' "
              . " OR ot.slug LIKE '%" . $slug . "%'"
              . " OR e.email LIKE '%" . $q . "%' ";
            if (strlen(preg_replace('/\D+/', '', $q)) > 6 && 0 == substr_count($q, '@')) {
                $whereCondition .= " OR coord.phone LIKE '%" . preg_replace('/\D+/', '', $q) . "%' "
                . " OR coord.mobilePhone LIKE '%" . preg_replace('/\D+/', '', $q) . "%' ";
            }
            $dql->andWhere($whereCondition);
        }
        if ($entityId) {
            $dql->leftJoin('o.entity', 'entity');
            if ($show_SharedContents) {
                $dql->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND o.isShared=1)');
            } else {
                $dql->andWhere('entity.id=' . $entityId . '');
            }
        }
        if (is_array($readerLimitations)) {
            if (array_key_exists('organizationTypeIds', $readerLimitations) && is_array($readerLimitations['organizationTypeIds']) && count($readerLimitations['organizationTypeIds']) && 'off' == $readerLimitations['organizationType_noLimitation']) {
                $dql->andwhere('ot.id IS NULL OR ot.id  IN (' . implode(',', $readerLimitations['organizationTypeIds']) . ')');
            }
            if (array_key_exists('tagIds', $readerLimitations) && is_array($readerLimitations['tagIds']) && count($readerLimitations['tagIds']) && 'off' == $readerLimitations['tag_noLimitation']) {
                $dql->leftJoin('o.tags', 'tag');
                $dql->andwhere('tag.id IS NULL OR tag.id  IN (' . implode(',', $readerLimitations['tagIds']) . ')');
            }
        }

        return $this->_em->createQuery($dql);
    }

    public function advancedSearch($searchParams, $entityId = null, $readerLimitations = null, $show_SharedContents = true, $fromApi = false)
    {
        $dql = $this->createQueryBuilder('o')
            ->select('o, ot, coord, city, tag, email, ety, rep, evt, tag')
            ->distinct()
            ->leftJoin('o.organizationType', 'ot')
            ->leftJoin('o.coordinate', 'coord')
            ->leftJoin('coord.email', 'email')
            ->leftJoin('coord.city', 'city')
            ->leftJoin('coord.representation', 'rep')
            ->leftJoin('coord.event', 'evt')
            ->leftJoin('o.entity', 'ety')
            ->leftJoin('city.territories', 'territory')
            ->leftJoin('o.tags', 'tag')
            ->orderby('o.name', 'ASC')
            ->where('o.deletedAt IS NULL')
            //->groupBy('o.id')
        ;
        $eligibaleCriteria = true;

        if (count(array_filter($searchParams)) > 1 && !(in_array(count(array_filter($searchParams)), [4, 6]) && (isset($searchParams['organizationType_sub']) && 'on' == $searchParams['organizationType_sub']) && (isset($searchParams['territory_sub']) && (isset($searchParams['territory_sub']) && 'on' == $searchParams['territory_sub'])) && (isset($searchParams['group_sub']) && 'on' == $searchParams['group_sub']))) {
            $eligibaleCriteria = false;
            if (isset($searchParams['organizationIds']) && is_array($searchParams['organizationIds']) && $searchParams['organizationIds'] !== []) {
                if (isset($searchParams['organization_exclusion']) && 'on' == $searchParams['organization_exclusion']) {
                    $dql->andwhere('( o.id is null or o.id  NOT IN (' . implode($searchParams['organizationIds'], ',') . ') )');
                } else {
                    $dql->andwhere('o.id  IN (' . implode(',', $searchParams['organizationIds']) . ')');
                }
                $eligibaleCriteria = true;
            }
            if (isset($searchParams['organizationTypeIds']) && is_array($searchParams['organizationTypeIds']) && $searchParams['organizationTypeIds'] !== []) {
                if (isset($searchParams['organizationType_exclusion']) && 'on' == $searchParams['organizationType_exclusion']) {
                    $dql->andwhere('( ot.id is null or ot.id  NOT IN (' . implode($searchParams['organizationTypeIds'], ',') . ') )');
                } else {
                    $dql->andwhere('ot.id  IN (' . implode(',', $searchParams['organizationTypeIds']) . ')');
                }
                $eligibaleCriteria = true;
            }
            if (isset($searchParams['cityIds']) && is_array($searchParams['cityIds']) && $searchParams['cityIds'] !== []) {
                if (isset($searchParams['city_exclusion']) && 'on' == $searchParams['city_exclusion']) {
                    $dql->andwhere('( city.id is null or city.id  NOT IN (' . implode(',', $searchParams['cityIds']) . ') )');
                } else {
                    $dql->andwhere('city.id  IN (' . implode(',', $searchParams['cityIds']) . ')');
                }
                $eligibaleCriteria = true;
            }
            if (isset($searchParams['departmentIds']) && is_array($searchParams['departmentIds']) && $searchParams['departmentIds'] !== []) {
                $slugs = "'" . implode("','", $searchParams['departmentIds']) . "'";
                if (isset($searchParams['department_exclusion']) && 'on' == $searchParams['department_exclusion']) {
                    $dql->andwhere("( city.slugDepartment is null or city.slugDepartment NOT IN ($slugs) )");
                } else {
                    $dql->andwhere("city.slugDepartment IN ($slugs)");
                }
                $eligibaleCriteria = true;
            }
            if (isset($searchParams['territoryIds']) && is_array($searchParams['territoryIds']) && $searchParams['territoryIds'] !== []) {
                if (isset($searchParams['territory_exclusion']) && 'on' == $searchParams['territory_exclusion']) {
                    $dql->andwhere('( territory.id is null or territory.id  NOT IN (' . implode(',', $searchParams['territoryIds']) . ') )');
                } else {
                    $dql->andwhere('territory.id  IN (' . implode(',', $searchParams['territoryIds']) . ')');
                    $dql->andWhere('territory.deletedAt IS NULL');
                }
                $eligibaleCriteria = true;
            }
            if (isset($searchParams['tagIds']) && is_array($searchParams['tagIds']) && $searchParams['tagIds'] !== []) {
                if (isset($searchParams['tag_exclusion']) && 'on' == $searchParams['tag_exclusion']) {
                    // récupération des o.id associés aux tags à exclure
                    $subQueryBuilder = $this->createQueryBuilder('o')
                    ->select('o.id')
                    ->distinct()
                    ->leftJoin('o.tags', 'tag')
                    ->where('tag.id  IN (' . implode(',', $searchParams['tagIds']) . ')');
                    $subQueryResult = $subQueryBuilder->getQuery()->getScalarResult();
                    $excludeIds = array_column($subQueryResult, 'id');
                    if (($excludeIds !== []) > 0) {
                        $dql->andwhere('( o.id  NOT IN (' . implode(',', $excludeIds) . ') )');
                    }
                } else {
                    $dql->andwhere('tag.id  IN (' . implode(',', $searchParams['tagIds']) . ')');
                }
                $eligibaleCriteria = true;
            }
            if (isset($searchParams['mandateTypeIds']) && is_array($searchParams['mandateTypeIds']) && $searchParams['mandateTypeIds'] !== []) {
                $dql->leftJoin('o.representations', 'reps')
                ->leftJoin('reps.mandateType', 'mt');
                if (isset($searchParams['mandateType_exclusion']) && 'on' == $searchParams['mandateType_exclusion']) {
                    $dql->andwhere('( mt.id is null or mt.id  NOT IN (' . implode(',', $searchParams['mandateTypeIds']) . ') )');
                } else {
                    $dql->andwhere('mt.id IN (' . implode(',', $searchParams['mandateTypeIds']) . ')');
                }
                $eligibaleCriteria = true;
            }
            if (isset($searchParams['groupIds']) && is_array($searchParams['groupIds']) && $searchParams['groupIds'] !== []) {
                $dql->leftJoin('o.groups', 'g');
                if (isset($searchParams['group_exclusion']) && 'on' == $searchParams['group_exclusion']) {
                    // récupération des r.id associés aux groupes à exclure
                    $subQueryBuilder = $this->createQueryBuilder('o')
                    ->select('o.id')
                    ->distinct()
                    ->leftJoin('o.groups', 'g')
                    ->where('g.id  IN (' . implode(',', $searchParams['groupIds']) . ')');
                    $subQueryResult = $subQueryBuilder->getQuery()->getScalarResult();
                    $excludeIds = array_column($subQueryResult, 'id');
                    if (($excludeIds !== []) > 0) {
                        $dql->andwhere('( o.id  NOT IN (' . implode(',', $excludeIds) . ') )');
                    }
                } else {
                    $dql->andwhere('g.id  IN (' . implode(',', $searchParams['groupIds']) . ')');
                }
                $eligibaleCriteria = true;
            }
            if (isset($searchParams['maxUpdatedDate']) && '' != $searchParams['maxUpdatedDate']) {
                $maxDate = new \DateTime($searchParams['maxUpdatedDate']);
                $dql->andWhere("o.updated <='". $maxDate->format('Y-m-d')."'");
            }
            if (isset($searchParams['createdByIds']) && count($searchParams['createdByIds']) > 0) {
                $dql->andwhere('o.createdBy  IN (' . implode(',', $searchParams['createdByIds']) . ')');
            }

            if (isset($searchParams['observation']) && '' != $searchParams['observation']) {
                $dql->andWhere('o.observation LIKE \'%' . str_replace('\'', '_', $searchParams['observation']) . '%\'');
                $eligibaleCriteria = true;
            }

            if (array_key_exists('onlyWithEmail', $searchParams) && $searchParams['onlyWithEmail']) {
                $dql->andwhere('email.email != \'\'');
            }
            
            if (isset($searchParams['createdByEntitiesIds']) && count($searchParams['createdByEntitiesIds']) > 0) {
                $dql->leftJoin('o.entity', 'entity');
                $dql->andwhere('entity.id IN (' . implode(',', $searchParams['createdByEntitiesIds']) . ') AND o.isShared=1');
                $entityId = null;
            }
        }
        if ($entityId) {
            $dql->leftJoin('o.entity', 'entity');
            if ($show_SharedContents) {
                $dql->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND o.isShared=1)');
            } else {
                $dql->andWhere('entity.id=' . $entityId . '');
            }
        }

        if (is_array($readerLimitations)) {
            if (array_key_exists('organizationTypeIds', $readerLimitations)  && is_array($readerLimitations['organizationTypeIds']) && count($readerLimitations['organizationTypeIds']) && 'off' == $readerLimitations['organizationType_noLimitation']) {
                $dql->andwhere('ot.id IS NULL OR ot.id  IN (' . implode(',', $readerLimitations['organizationTypeIds']) . ')');
            }
            if (array_key_exists('tagIds', $readerLimitations) && is_array($readerLimitations['tagIds']) && count($readerLimitations['tagIds']) && 'off' == $readerLimitations['tag_noLimitation']) {
                $dql->andwhere('tag.id IS NULL OR tag.id  IN (' . implode(',', $readerLimitations['tagIds']) . ')');
            }
        }

        if (!$eligibaleCriteria && !$fromApi) {
            // on ne souhaite pas afficher d'organismes
            $dql->andWhere('1=0');
        }

        return $this->_em->createQuery($dql);
    }

    public function autoCompleteAbbreviation($q, $entityId = null, $show_SharedContents = true, $page_limit = 30, $page = null)
    {
        $dql = $this->createQueryBuilder('o')
            ->orderby('o.name', 'ASC')
            ->where('o.deletedAt IS NULL')
        ;
        if ($q) {
            $dql->andWhere("o.abbreviation LIKE '%" . str_replace("'", "\'", $q) . "%'");
        }
        if ($entityId) {
            $dql->leftJoin('o.entity', 'entity');
            if ($show_SharedContents) {
                $dql->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND o.isShared=1)');
            } else {
                $dql->andWhere('entity.id=' . $entityId . '');
            }
        }
        $query = $this->_em->createQuery($dql);
        $query->setMaxResults($page_limit);
        if ($page) {
            $query->setFirstResult(($page - 1) * $page_limit);
        }

        return $query->getResult();
    }

    public function getKeyPair($entityId = null, $show_SharedContents = true)
    {
        $sql = "SELECT o.id, o.name FROM organization o WHERE o.name <> '' AND deletedAt IS NULL";

        if ($entityId) {
            if ($show_SharedContents) {
                $sql .= ' AND (o.entity_id=' . $entityId . ' OR (o.entity_id!=' . $entityId . ' AND o.is_shared=1))';
            } else {
                $sql .= ' AND o.entity_id=' . $entityId;
            }
        }

        $sql .= ' ORDER BY o.name';
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function getAbbreviationKeyPair($entityId = null, $show_SharedContents = true)
    {
        $sql = "SELECT o.id, o.abbreviation FROM organization o WHERE o.abbreviation <> '' AND deletedAt IS NULL";
        if ($entityId) {
            if ($show_SharedContents) {
                $sql .= ' AND (o.entity_id=' . $entityId . ' OR (o.entity_id!=' . $entityId . ' AND o.is_shared=1))';
            } else {
                $sql .= ' AND o.entity_id=' . $entityId;
            }
        }
        $sql .= ' ORDER BY o.abbreviation';
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function getListForSelection($organizationIds)
    {
        // warning, ne pas changer le prefix p
        $dql = $this->createQueryBuilder('p')
            ->select('p, co, ot, rep, evt, ety, city, email')
            ->leftJoin('p.coordinate', 'co')
            ->leftJoin('co.email', 'email')
            ->leftJoin('co.city', 'city')
            ->leftJoin('p.entity', 'ety')
            ->leftJoin('p.organizationType', 'ot')
            ->leftJoin('co.representation', 'rep')
            ->leftJoin('co.event', 'evt')
            ->where('p.id IN (' . implode(',', $organizationIds) . ')')
            ->andWhere('p.deletedAt IS NULL')
            ->orderby('p.name', 'ASC')
        ;

        return $this->_em->createQuery($dql);
    }

    public function getOrganizationWithoutEmails($organizationIds)
    {
        $dql = $this->createQueryBuilder('o')
            ->leftJoin('o.coordinate', 'coord')
            ->leftJoin('coord.email', 'email')
            ->where('o.id IN (' . implode(',', $organizationIds) . ')')
            ->andWhere('email.id IS NULL')
            ->andWhere('o.deletedAt IS NULL')
        ;
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function getListForMassiveDocumentGeneration($organizationIds)
    {
        $dql = $this->createQueryBuilder('o')
            ->select('o as object, o.id as o_id, coord.addressLine1, coord.addressLine2, coord.cedex, city.name as cityName, city.zipCode as zipcode, o.name as o_name, o.slug as slug')
            ->leftJoin('o.coordinate', 'coord')
            ->leftJoin('coord.city', 'city')
//            ->leftJoin('o.representations', 'reps')
//            ->leftJoin('reps.mandateType', 'mt')
            ->where('o.id IN (' . implode(',', $organizationIds) . ')')
            ->andWhere('o.deletedAt IS NULL')
            ->orderby('o.name', 'ASC')
        ;
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function getAllOrganizationLinkedIds($organizationId)
    {
        $dql = $this->createQueryBuilder('o')
            ->select('o.id')
            ->distinct()
            ->leftJoin('o.organizationOriginLinks', 'ool')
            ->leftJoin('ool.organizationLinked', 'oolLinked')
            ->leftJoin('ool.organizationOrigin', 'oolOrigin')
            ->leftJoin('o.organizationLinkedLinks', 'oll')
            ->leftJoin('oll.organizationLinked', 'ollLinked')
            ->leftJoin('oll.organizationOrigin', 'ollOrigin')
            ->where('oolLinked.id=' . $organizationId)
            ->orWhere('oolOrigin.id=' . $organizationId)
            ->orWhere('ollLinked.id=' . $organizationId)
            ->orWhere('ollOrigin.id=' . $organizationId)
            ->andWhere('o.id!=' . $organizationId)
            ->andWhere('o.deletedAt IS NULL')
        ;
        $query = $this->_em->createQuery($dql);
        $result = $query->getScalarResult();

        return array_map('current', $result);
    }

    public function getSubServiceOrganizations($organizationId)
    {
        $dql = $this->createQueryBuilder('o')
            ->leftJoin('o.organizationLinkedLinks', 'oll')
            ->leftJoin('oll.organizationOrigin', 'ollOrigin')
            ->where('ollOrigin.id=' . $organizationId)
            ->andWhere('oll.linkType=3')
            ->andWhere('o.deletedAt IS NULL');
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function searchOrganizationForImport($organizationSlug, $insee = null, $entityId = null)
    {
        $dql = $this->createQueryBuilder('o')
            ->select('o')
            ->leftJoin('o.coordinate', 'coord')
            ->leftJoin('coord.email', 'email')
            ->leftJoin('coord.city', 'city')
            ->where('o.slug LIKE \'' . $organizationSlug . '%\'');
        if ($insee) {
            $dql->andWhere('city.insee=\'' . $insee . '\'');
        }
        if ($entityId) {
            $dql->leftJoin('o.entity', 'ety');
            $dql->andWhere('ety.id=' . $entityId);
        }

        $query = $this->_em->createQuery($dql);
        $query->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    public function searchDuplicateElements($entityId = null)
    {
        $dql = $this->createQueryBuilder('o')
        ->select('o as object, count(o) as nb')
        ->leftJoin('o.coordinate', 'coord')
        ->leftJoin('coord.city', 'city')
        ->leftJoin('o.entity', 'entity')
        ->groupBy("o.name,city.insee")
        ->orderBy('nb', 'DESC')
        ->having('nb > 1')
        ;
        if ($entityId) {
            $dql->andwhere('entity.deletedAt IS NULL');
            if ($show_SharedContents) {
                $dql->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND p.isShared=1)');
            } else {
                $dql->andWhere('entity.id=' . $entityId . '');
            }
        }
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function getDuplicatesElements($organization, $entityId = null, $exludeId = null)
    {
        $searchInsee = ($organization->getCoordinate() && $organization->getCoordinate()->getCity()) ? $organization->getCoordinate()->getCity()->getInsee() : null;

        $dql = $this->createQueryBuilder('o')
        ->select('o')
        ->leftJoin('o.coordinate', 'coord')
        ->leftJoin('coord.city', 'city')
        ->leftJoin('o.entity', 'entity')
        ->where('o.name LIKE \'' . str_replace("'", "_", $organization->getName()) . '\'')
        ;
        if ($searchInsee) {
            $dql->andWhere('city.insee = \'' . $searchInsee . '\'');
        }
        if ($entityId) {
            $dql->andwhere('entity.deletedAt IS NULL');
            if ($show_SharedContents) {
                $dql->andWhere('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND p.isShared=1)');
            } else {
                $dql->andWhere('entity.id=' . $entityId . '');
            }
        }
        if ($exludeId) {
            $dql->andWhere('o.id != ' . $exludeId . '');
        }
        $query = $this->_em->createQuery($dql);
        //echo $query->getSQL();die;

        return $query->getResult();
    }
}
