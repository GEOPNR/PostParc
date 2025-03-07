<?php

namespace PostparcBundle\Repository;

/**
 * NoteRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ScanQrcodeStatRepository extends \Doctrine\ORM\EntityRepository
{
    public function getObjectStats($className, $objectId)
    {
        $dql = $this->createQueryBuilder('s')->select('s')
                ->where("s.className='" . $className . "'")
                ->andwhere('s.objectId=' . $objectId)
                ;

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }
    
    public function getStatsQuery($filter = []) {
        //dump($filter); die;
        $dql = $this->createQueryBuilder('s')
                ->select('s.completeName, count(s.id) as nb')
                ;
        if(array_key_exists('startDate', $filter) && $filter['startDate']){
            $dql->andWhere('s.created >= \''.$filter['startDate']->format('Y-m-d').'\'');
        }
        if(array_key_exists('endDate', $filter) && $filter['endDate']){
            $dql->andWhere('s.created <= \''.$filter['endDate']->format('Y-m-d').'\'');
        }
        if(array_key_exists('className', $filter) && count($filter['className'])){
            $dql->andWhere("s.className IN ('".implode("','",$filter['className'])."')");
        }
        if(array_key_exists('entityID', $filter) && $filter['entityID']){
            $dql->andWhere("s.entityID = ".$filter['entityID']);
        }
        // $pagination
        $dql->setMaxResults($filter['per_page']);
        $dql->setFirstResult((($filter['per_page']-1)*$filter['per_page'])+1);
        
        $dql->orderBy($filter['sort'],$filter['direction']);
        $dql->groupBy('s.completeName');
        
        $query = $this->_em->createQuery($dql);

        return $query->execute();
    }
}
