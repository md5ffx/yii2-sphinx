<?php

namespace yiiunit\extensions\sphinx;

use yii\sphinx\ActiveDataProvider;
use yii\sphinx\Query;
use Yii;
use yii\web\Request;
use yiiunit\extensions\sphinx\data\ar\ActiveRecord;
use yiiunit\extensions\sphinx\data\ar\ArticleIndex;

/**
 * @group sphinx
 */
class ActiveDataProviderTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
    }

    // Tests :

    public function testQuery()
    {
        $query = new Query();
        $query->from('yii2_test_article_index');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
        ]);
        $models = $provider->getModels();
        $this->assertEquals(20, count($models));

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
            'pagination' => [
                'pageSize' => 1,
            ]
        ]);
        $models = $provider->getModels();
        $this->assertEquals(1, count($models));
    }

    public function testActiveQuery()
    {
        $provider = new ActiveDataProvider([
            'query' => ArticleIndex::find()->orderBy('id ASC'),
        ]);
        $models = $provider->getModels();
        $this->assertEquals(20, count($models));
        $this->assertTrue($models[0] instanceof ArticleIndex);
        $this->assertTrue($models[1] instanceof ArticleIndex);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20], $provider->getKeys());

        $provider = new ActiveDataProvider([
            'query' => ArticleIndex::find(),
            'pagination' => [
                'pageSize' => 1,
            ]
        ]);
        $models = $provider->getModels();
        $this->assertEquals(1, count($models));
    }

    /**
     * @depends testQuery
     */
    public function testFacetQuery()
    {
        $query = new Query();
        $query->from('yii2_test_article_index');
        $query->facets([
            'author_id'
        ]);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
        ]);
        $models = $provider->getModels();
        $this->assertEquals(20, count($models));
        $this->assertEquals(10, count($provider->getFacet('author_id')));
    }

    /**
     * @depends testQuery
     */
    public function testTotalCountFromMeta()
    {
        $query = (new Query())
            ->from('yii2_test_article_index')
            ->showMeta(true);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
            'pagination' => [
                'pageSize' => 1,
            ]
        ]);
        $models = $provider->getModels();
        $this->assertEquals(1, count($models));
        $this->assertEquals(1002, $provider->getTotalCount());
    }

    /**
     * @depends testTotalCountFromMeta
     *
     * @see https://github.com/yiisoft/yii2-sphinx/issues/11
     */
    public function testAutoAdjustPagination()
    {
        $request = new Request();
        $request->setQueryParams(['page' => 2]);
        Yii::$app->set('request', $request);

        $query = (new Query())
            ->from('yii2_test_article_index')
            ->orderBy(['id' => SORT_ASC])
            ->showMeta(true);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
            'pagination' => [
                'pageSize' => 1,
            ]
        ]);
        $models = $provider->getModels();
        $this->assertEquals(2, $models[0]['id']);
    }

    /**
     * @depends testAutoAdjustPagination
     *
     * @see https://github.com/yiisoft/yii2-sphinx/issues/12
     */
    public function testAutoAdjustMaxMatches()
    {
        $request = new Request();
        $request->setQueryParams(['page' => 99999]);
        Yii::$app->set('request', $request);

        $query = (new Query())
            ->from('yii2_test_article_index')
            ->orderBy(['id' => SORT_ASC]);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
            'pagination' => [
                'pageSize' => 100,
                'validatePage' => false,
            ]
        ]);
        $models = $provider->getModels();
        $this->assertEmpty($models); // no exception
    }

    public function testMatch()
    {
        $query = (new Query())
            ->from('yii2_test_article_index')
            ->match('Repeated');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
        ]);

        $this->assertEquals(1002, $provider->getTotalCount());

        $query->match('Excepturi');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
        ]);

        $this->assertEquals(29, $provider->getTotalCount());
    }
}
