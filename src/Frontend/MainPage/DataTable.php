<?php


namespace App\Frontend\MainPage;


use App\Entity\Jcr\JournalWosCategory;
use App\Entity\Journal\Journal;
use App\Lib\Utils\IssnUtils;
use PaLabs\DatagridBundle\DataTable\AbstractConfigurableDataTable;
use PaLabs\DatagridBundle\DataTable\Column\ColumnsBuilder;
use PaLabs\DatagridBundle\DataTable\Column\Type\NumberingColumn;
use PaLabs\DatagridBundle\DataTable\ColumnMakerContext;
use PaLabs\DatagridBundle\DataTable\DataTableSettings;
use PaLabs\DatagridBundle\Field\Renderer\FieldRenderer;
use PaLabs\DatagridBundle\Field\Type\String\StringField;
use PaLabs\DatagridBundle\Field\Type\Url\UrlField;
use PaLabs\DatagridBundle\Grid\GridParameters;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataTable extends AbstractConfigurableDataTable
{
    protected $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct();
        $this->urlGenerator = $urlGenerator;
    }

    protected function defaultSettings(GridParameters $parameters): DataTableSettings
    {
        return new DataTableSettings(['name', 'issn', 'publisher', 'period', 'articles',
            'jcr_impact_2', 'jcr_quartile']);
    }

    protected function configureColumns(ColumnsBuilder $builder, GridParameters $parameters)
    {
        $builder
            ->add(new NumberingColumn());

        $builder->addColumns([
            'name' => function (Journal $entity) {
                $url = $this->urlGenerator->generate('journal_view', ['id' => $entity->getId()]);
                return UrlField::field($url, $entity->getName());
            },
            'issn' => function (Journal $entity) {
                return StringField::field(IssnUtils::formatIssnWithHyphen($entity->getIssn()));
            },
            'publisher' => function (Journal $entity) {
                return $entity->getStat() === null ? StringField::field() :
                    StringField::field($entity->getStat()->getPublisher());
            },
            'period' => function (Journal $entity) {
                return $entity->getStat() === null ? StringField::field() :
                    StringField::field(sprintf('%d-%d',
                        $entity->getStat()->getArticleMinYear(),
                        $entity->getStat()->getArticleMaxYear()));
            },
            'articles' => function (Journal $entity) {
                return $entity->getStat() === null ? StringField::field() :
                    StringField::field($entity->getStat()->getArticlesCount());
            },
            'jcr_impact_2' => function (ColumnMakerContext $context) {
                return StringField::field($context->getRow()['jcrImpact2'] ?? '');
            },
            'jcr_quartile' => function (ColumnMakerContext $context) {
                if (empty($context->getRow()['jcrQuartile'])) {
                    return StringField::field();
                }
                return StringField::field(sprintf('Q%s', $context->getRow()['jcrQuartile']));
            },
            'wos_articles' => function (Journal $entity) {
                return $entity->getStat() === null ? StringField::field() :
                    StringField::field($entity->getStat()->getWosArticlesCount());
            },
            'wos_category' => function (Journal $entity) {
                $fields = array_map(function (JournalWosCategory $category) {
                    return StringField::field($category->getCategory()->getName());
                }, $entity->getWosCategories()->toArray());
                return FieldRenderer::multiValueField($fields, true);
            },
            'id' => function (Journal $entity) {
                return StringField::field($entity->getId());
            },
        ], [
            'name' => 'Название',
            'issn' => 'ISSN',
            'publisher' => 'Издатель',
            'period' => 'Период',
            'articles' => 'Статей',
            'jcr_impact_2' => 'IF JCR (2-летний)',
            'jcr_quartile' => 'Квартиль JCR',
            'wos_articles' => 'Статей (Web of knowledge)',
            'wos_category' => 'Категория WOS',
            'id' => 'ID'
        ]);
    }
}