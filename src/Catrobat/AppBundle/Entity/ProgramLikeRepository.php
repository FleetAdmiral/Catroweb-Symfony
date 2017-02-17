<?php

namespace Catrobat\AppBundle\Entity;

use Doctrine\ORM\EntityRepository;


/**
 * ProgramLikeRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProgramLikeRepository extends EntityRepository
{
    /**
     * @param int $program_id
     * @param int $type
     * @return int
     */
    public function likeTypeCount($program_id, $type)
    {
        $qb = $this->createQueryBuilder('l');

        $result = $qb
            ->select('l')
            ->where($qb->expr()->eq('l.program_id', ':program_id'))
            ->andWhere($qb->expr()->eq('l.type', ':type'))
            ->setParameter(':program_id', $program_id)
            ->setParameter(':type', $type)
            ->distinct()
            ->getQuery()
            ->getResult();

        return count($result);
    }

    /**
     * @param int $program_id
     * @return int
     */
    public function totalLikeCount($program_id)
    {
        $qb = $this->createQueryBuilder('l');

        $result = $qb
            ->select('l')
            ->where($qb->expr()->eq('l.program_id', ':program_id'))
            ->setParameter(':program_id', $program_id)
            ->distinct()
            ->getQuery()
            ->getResult();

        return count($result);
    }
}