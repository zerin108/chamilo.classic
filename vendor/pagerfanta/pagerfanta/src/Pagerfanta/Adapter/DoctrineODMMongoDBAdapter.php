<?php

/*
 * This file is part of the Pagerfanta package.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pagerfanta\Adapter;

use Doctrine\ODM\MongoDB\Query\Builder;

/**
 * DoctrineODMMongoDBAdapter.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
class DoctrineODMMongoDBAdapter implements AdapterInterface
{
    private $queryBuilder;

    /**
     * Constructor.
     *
     * @param Builder $queryBuilder A DoctrineMongo query builder.
     *
     * @api
     */
    public function __construct(Builder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Returns the query builder.
     *
     * @return Builder The query builder.
     *
     * @api
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        return $this->queryBuilder->getQuery()->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        return iterator_to_array($this->queryBuilder->limit($length)->skip($offset)->getQuery()->execute());
    }
}
