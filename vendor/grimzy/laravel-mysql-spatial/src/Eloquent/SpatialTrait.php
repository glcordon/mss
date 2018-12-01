<?php

namespace Grimzy\LaravelMysqlSpatial\Eloquent;

use Grimzy\LaravelMysqlSpatial\Exceptions\SpatialFieldsNotDefinedException;
use Grimzy\LaravelMysqlSpatial\Types\Geometry;
use Grimzy\LaravelMysqlSpatial\Types\GeometryInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Trait SpatialTrait.
 *
 * @method static distance($geometryColumn, $geometry, $distance)
 * @method static distanceExcludingSelf($geometryColumn, $geometry, $distance)
 * @method static distanceSphere($geometryColumn, $geometry, $distance)
 * @method static distanceSphereExcludingSelf($geometryColumn, $geometry, $distance)
 * @method static comparison($geometryColumn, $geometry, $relationship)
 * @method static within($geometryColumn, $polygon)
 * @method static crosses($geometryColumn, $geometry)
 * @method static contains($geometryColumn, $geometry)
 * @method static disjoint($geometryColumn, $geometry)
 * @method static equals($geometryColumn, $geometry)
 * @method static intersects($geometryColumn, $geometry)
 * @method static overlaps($geometryColumn, $geometry)
 * @method static doesTouch($geometryColumn, $geometry)
 */
trait SpatialTrait
{
    /*
     * The attributes that are spatial representations.
     * To use this Trait, add the following array to the model class
     *
     * @var array
     *
     * protected $spatialFields = [];
     */

    public $geometries = [];

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Grimzy\LaravelMysqlSpatial\Eloquent\Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    protected function performInsert(EloquentBuilder $query, array $options = [])
    {
        foreach ($this->attributes as $key => $value) {
            if ($value instanceof GeometryInterface) {
                $this->geometries[$key] = $value; //Preserve the geometry objects prior to the insert
                $this->attributes[$key] = $this->getConnection()->raw(sprintf("ST_GeomFromText('%s')", $value->toWKT()));
            }
        }

        $insert = parent::performInsert($query, $options);

        foreach ($this->geometries as $key => $value) {
            $this->attributes[$key] = $value; //Retrieve the geometry objects so they can be used in the model
        }

        return $insert; //Return the result of the parent insert
    }

    public function setRawAttributes(array $attributes, $sync = false)
    {
        $spatial_fields = $this->getSpatialFields();

        foreach ($attributes as $attribute => &$value) {
            if (in_array($attribute, $spatial_fields) && is_string($value) && strlen($value) >= 15) {
                $value = Geometry::fromWKB($value);
            }
        }

        return parent::setRawAttributes($attributes, $sync);
    }

    public function getSpatialFields()
    {
        if (property_exists($this, 'spatialFields')) {
            return $this->spatialFields;
        } else {
            throw new SpatialFieldsNotDefinedException(__CLASS__.' has to define $spatialFields');
        }
    }

    public function scopeDistance($query, $geometryColumn, $geometry, $distance)
    {
        $query->whereRaw("st_distance(`{$geometryColumn}`, ST_GeomFromText('{$geometry->toWkt()}')) <= {$distance}");

        return $query;
    }

    public function scopeDistanceExcludingSelf($query, $geometryColumn, $geometry, $distance)
    {
        $query = $this->scopeDistance($query, $geometryColumn, $geometry, $distance);

        $query->whereRaw("st_distance(`{$geometryColumn}`, ST_GeomFromText('{$geometry->toWkt()}')) != 0");

        return $query;
    }

    public function scopeDistanceValue($query, $geometryColumn, $geometry)
    {
        $columns = $query->getQuery()->columns;

        if (!$columns) {
            $query->select('*');
        }
        $query->selectRaw("st_distance(`{$geometryColumn}`, ST_GeomFromText('{$geometry->toWkt()}')) as distance");
    }

    public function scopeDistanceSphere($query, $geometryColumn, $geometry, $distance)
    {
        $query->whereRaw("st_distance_sphere(`{$geometryColumn}`, ST_GeomFromText('{$geometry->toWkt()}')) <= {$distance}");

        return $query;
    }

    public function scopeDistanceSphereExcludingSelf($query, $geometryColumn, $geometry, $distance)
    {
        $query = $this->scopeDistanceSphere($query, $geometryColumn, $geometry, $distance);

        $query->whereRaw("st_distance_sphere(`{$geometryColumn}`, ST_GeomFromText('{$geometry->toWkt()}')) != 0");

        return $query;
    }

    public function scopeDistanceSphereValue($query, $geometryColumn, $geometry)
    {
        $columns = $query->getQuery()->columns;

        if (!$columns) {
            $query->select('*');
        }
        $query->selectRaw("st_distance_sphere(`{$geometryColumn}`, ST_GeomFromText('{$geometry->toWkt()}')) as distance");
    }

    public function scopeComparison($query, $geometryColumn, $geometry, $relationship)
    {
        $query->whereRaw("st_{$relationship}(`{$geometryColumn}`, ST_GeomFromText('{$geometry->toWkt()}'))");

        return $query;
    }

    public function scopeWithin($query, $geometryColumn, $polygon)
    {
        return $this->scopeComparison($query, $geometryColumn, $polygon, 'within');
    }

    public function scopeCrosses($query, $geometryColumn, $geometry)
    {
        return $this->scopeComparison($query, $geometryColumn, $geometry, 'crosses');
    }

    public function scopeContains($query, $geometryColumn, $geometry)
    {
        return $this->scopeComparison($query, $geometryColumn, $geometry, 'contains');
    }

    public function scopeDisjoint($query, $geometryColumn, $geometry)
    {
        return $this->scopeComparison($query, $geometryColumn, $geometry, 'disjoint');
    }

    public function scopeEquals($query, $geometryColumn, $geometry)
    {
        return $this->scopeComparison($query, $geometryColumn, $geometry, 'equals');
    }

    public function scopeIntersects($query, $geometryColumn, $geometry)
    {
        return $this->scopeComparison($query, $geometryColumn, $geometry, 'intersects');
    }

    public function scopeOverlaps($query, $geometryColumn, $geometry)
    {
        return $this->scopeComparison($query, $geometryColumn, $geometry, 'overlaps');
    }

    public function scopeDoesTouch($query, $geometryColumn, $geometry)
    {
        return $this->scopeComparison($query, $geometryColumn, $geometry, 'touches');
    }
}
