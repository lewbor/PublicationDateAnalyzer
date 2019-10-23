<?php


namespace App\Frontend\MainPage;


use App\Entity\Journal;
use App\Entity\JournalAnalytics;
use PaLabs\DatagridBundle\DataTable\AbstractConfigurableDataTable;
use PaLabs\DatagridBundle\DataTable\Column\ColumnsBuilder;
use PaLabs\DatagridBundle\DataTable\Column\Type\NumberingColumn;
use PaLabs\DatagridBundle\DataTable\ColumnMakerContext;
use PaLabs\DatagridBundle\DataTable\DataTableSettings;
use PaLabs\DatagridBundle\Field\Type\String\StringField;
use PaLabs\DatagridBundle\Field\Type\Url\UrlField;
use PaLabs\DatagridBundle\Grid\GridParameters;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataTable  extends AbstractConfigurableDataTable
{
    protected $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct();
        $this->urlGenerator = $urlGenerator;
    }

    protected function defaultSettings(GridParameters $parameters): DataTableSettings
    {
        return new DataTableSettings(['name', 'issn', 'publisher', 'period', 'articles']);
    }

    protected function configureColumns(ColumnsBuilder $builder, GridParameters $parameters)
    {
        $builder
            ->add( new NumberingColumn());

        $builder->addColumns([
            'name' => function (Journal $entity) {
            $url = $this->urlGenerator->generate('journal_view', ['id' => $entity->getId()]);
                return UrlField::field($url, $entity->getName());
            },
            'issn' => function (Journal $entity) {
                return StringField::field($entity->getIssn());
            },
            'publisher'  => function (Journal $entity) {
                return StringField::field($entity->getCrossrefData()['publisher']);
            },
            'period' => function(Journal $entity, ColumnMakerContext $context) {
                $analytics = $this->journalAnalytics($entity, $context);
                return $analytics === null ? StringField::field() :
                    StringField::field(sprintf('%d-%d', $analytics->getAnalytics()['common']['min'], $analytics->getAnalytics()['common']['max']));
            },
            'articles' => function(Journal $entity, ColumnMakerContext $context) {
                $analytics = $this->journalAnalytics($entity, $context);
                return $analytics === null ? StringField::field() :
                    StringField::field($analytics->getAnalytics()['common']['count']);
            },
        ], [
            'name' => 'Название',
            'issn' => 'ISSN',
            'publisher' => 'Издатель',
            'period' => 'Период',
            'articles' => 'Статей'
        ]);
    }

    private function journalAnalytics(Journal $journal, ColumnMakerContext $context): ?JournalAnalytics {
        $journalAnalytics = $context->getPage()->getPageContext()->get(JournalAnalytics::class);
        return $journalAnalytics[$journal->getId()] ?? null;
    }
}