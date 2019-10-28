<?php


namespace App\Lib\Grid\Filter\WosCategory;


use App\Entity\Jcr\JournalWosCategory;
use App\Entity\Jcr\WosCategory;
use App\Lib\Form\Select2EntityForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WosCategorySearchForm extends AbstractType
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $data = $this->em->createQueryBuilder()
            ->select('entity', sprintf('(%s) as journalCount',
                $this->em->createQueryBuilder()
                    ->select('COUNT(journal_wos_category.id)')
                    ->from(JournalWosCategory::class, 'journal_wos_category')
                    ->andWhere('journal_wos_category.category = entity')
                    ->getDQL()))
            ->from(WosCategory::class, 'entity')
            ->having('journalCount > 0')
            ->orderBy('journalCount', 'desc')
            ->getQuery()
            ->getResult();

        $entities = [];
        $entitiesStat = [];
        foreach ($data as $row) {
            /** @var WosCategory $entity */
            $entity = $row[0];
            $entities[] = $entity;
            $entitiesStat[$entity->getId()] = (int)$row['journalCount'];
        }

        $resolver->setDefaults([
            'class' => WosCategory::class,
            'choices' => $entities,
            'choice_label' => function (WosCategory $category) use ($entitiesStat) {
                return sprintf('%s (%d)', $category->getName(), $entitiesStat[$category->getId()]);
            }
        ]);
    }

    public function getParent()
    {
        return Select2EntityForm::class;
    }
}