<?php
namespace Catrobat\AppBundle\RecommenderSystem;

use Catrobat\AppBundle\Entity\ProgramLikeRepository;
use Catrobat\AppBundle\Entity\UserManager;
use Catrobat\AppBundle\Entity\UserSimilarityRelation;
use Catrobat\AppBundle\Entity\UserSimilarityRelationRepository;
use Doctrine\ORM\EntityManager;


class RecommenderManager
{
    const RECOMMENDER_LOCK_FILE_NAME = 'CatrobatRecommender.lock';
    /**
     * @var EntityManager The entity manager.
     */
    private $entity_manager;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var UserSimilarityRelationRepository
     */
    private $user_similarity_relation_repository;

    /**
     * @var ProgramLikeRepository
     */
    private $program_like_repository;

    /**
     * @param EntityManager $entity_manager
     * @param UserManager $user_manager
     * @param UserSimilarityRelationRepository $user_similarity_relation_repository
     */
    public function __construct($entity_manager, $user_manager, $user_similarity_relation_repository, $program_like_repository)
    {
        $this->entity_manager = $entity_manager;
        $this->user_manager = $user_manager;
        $this->user_similarity_relation_repository = $user_similarity_relation_repository;
        $this->program_like_repository = $program_like_repository;
    }

    public function removeAllUserSimilarityRelations()
    {
        $this->user_similarity_relation_repository->removeAllUserRelations();
    }

    public function computeUserSimilarities($progress_bar = null)
    {
        $users = $this->user_manager->findAll();
        $rated_users = array_unique(array_filter($users, function ($user) {
            return (count($user->getLikes()) > 0);
        }));

        $already_added_relations = [];
        foreach ($rated_users as $first_user) {
            if ($progress_bar != null) {
                $progress_bar->setMessage('Computing similarity of user (#' . $first_user->getId() . ')');
            }

            $first_user_likes = $first_user->getLikes()->getValues();
            $ids_of_programs_liked_by_first_user = array_map(function ($like) {
                return $like->getProgramId();
            }, $first_user_likes);
            foreach ($rated_users as $second_user) {
                $key = $first_user->getId() . '_' . $second_user->getId();
                $reverse_key = $second_user->getId() . '_' . $first_user->getId();

                if (($first_user->getId() == $second_user->getId()) || in_array($key, $already_added_relations)
                    || in_array($reverse_key, $already_added_relations)
                ) {
                    continue;
                }

                $already_added_relations[] = $key;
                $second_user_likes = $second_user->getLikes()->getValues();
                $ids_of_programs_liked_by_second_user = array_map(function ($like) {
                    return $like->getProgramId();
                }, $second_user_likes);
                $ids_of_same_programs_liked_by_both = array_unique(array_intersect($ids_of_programs_liked_by_first_user, $ids_of_programs_liked_by_second_user));
                $ids_of_all_programs_liked_by_any_of_both = array_unique(array_merge($ids_of_programs_liked_by_first_user, $ids_of_programs_liked_by_second_user));
                $number_of_same_programs_liked_by_both = count($ids_of_same_programs_liked_by_both);
                $number_of_all_programs_liked_by_any_of_both = count($ids_of_all_programs_liked_by_any_of_both);

                if ($number_of_same_programs_liked_by_both == 0) {
                    continue;
                }

                $jaccard_similarity = floatval($number_of_same_programs_liked_by_both) / floatval($number_of_all_programs_liked_by_any_of_both);
                $similarity_relation = new UserSimilarityRelation($first_user, $second_user, $jaccard_similarity);
                $this->entity_manager->persist($similarity_relation);
                $this->entity_manager->flush($similarity_relation);
            }

            if ($progress_bar != null) {
                $progress_bar->clear();
                $progress_bar->advance();
                $progress_bar->display();
            }
        }
    }

    public function recommendProgramsOfSimilarUsers($user, $flavor)
    {
        $user_similarity_relations = $this->user_similarity_relation_repository->getRelationsOfSimilarUsers($user);
        $similar_user_similarity_mapping = [];

        foreach ($user_similarity_relations as $r) {
            $id_of_similar_user = ($r->getFirstUserId() != $user->getId()) ? $r->getFirstUserId() : $r->getSecondUserId();
            $similar_user_similarity_mapping[$id_of_similar_user] = $r->getSimilarity();
        }

        $ids_of_similar_users = array_keys($similar_user_similarity_mapping);
        $excluded_ids_of_liked_programs = array_unique(array_map(function ($like) {
            return $like->getProgramId();
        }, $user->getLikes()->getValues()));

        $differing_likes = $this->program_like_repository->getLikesOfUsers($ids_of_similar_users, $excluded_ids_of_liked_programs, $flavor);

        $recommendation_weights = [];
        $programs_liked_by_others = [];
        foreach ($differing_likes as $differing_like) {
            $key = $differing_like->getProgramId();
            assert(!in_array($key, $excluded_ids_of_liked_programs));

            if (!array_key_exists($key, $recommendation_weights)) {
                $recommendation_weights[$key] = 0.0;
                $programs_liked_by_others[$key] = $differing_like->getProgram();
            }

            $recommendation_weights[$key] += $similar_user_similarity_mapping[$differing_like->getUserId()];
        }

        arsort($recommendation_weights);

        return array_map(function ($program_id) use ($programs_liked_by_others) {
            return $programs_liked_by_others[$program_id];
        }, array_keys($recommendation_weights));
    }
}
