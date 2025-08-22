<?php

namespace Netsells\Http\Resources\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;

abstract class ResourceTestCase extends TestCase
{
    protected bool $dumpsQueryCount = false;

    protected bool $dumpsJson = false;

    protected int $collectionSize = 4;

    #[DataProvider('resourceProvider')]
    public function test_super_reduces_queries_over_basic_resource_and_both_match(string $basicClass, string $superClass)
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = $this->produce(1)->fresh();
        $basicResource = $this->withQueryLog($basicQueryLog, function () use ($basicClass, $model) {
            return $basicClass::make($model)->response()->content();
        });

        $model = $model->fresh();
        $superResource = $this->withQueryLog($superQueryLog, function () use ($superClass, $model) {
            return $superClass::make($model)->response()->content();
        });

        $this->assert($basicResource, $basicQueryLog, $superResource, $superQueryLog);
    }

    #[DataProvider('resourceProvider')]
    public function test_super_reduces_queries_over_basic_resource_collection_and_both_match(string $basicClass, string $superClass)
    {
        /** @var \Illuminate\Database\Eloquent\Collection $models */
        $models = $this->produce($this->collectionSize)->fresh();
        $basicResource = $this->withQueryLog($basicQueryLog, function () use ($basicClass, $models) {
            return $basicClass::collection($models)->response()->content();
        });

        $models = $models->fresh();
        $superResource = $this->withQueryLog($superQueryLog, function () use ($superClass, $models) {
            return $superClass::collection($models)->response()->content();
        });

        $this->assert($basicResource, $basicQueryLog, $superResource, $superQueryLog);
    }

    protected function assert(string $basicResource, array $basicQueryLog, string $superResource, array $superQueryLog)
    {
        $basicQueryCount = count($basicQueryLog);
        $superQueryCount = count($superQueryLog);

        if ($this->dumpsQueryCount) {
            if ($basicQueryCount < $superQueryCount) {
                dump([
                    'json' => $basicResource,
                    'basic' => $basicQueryLog,
                    'super' => $superQueryLog,
                ]);
            } else {
                dump($basicQueryCount.' >= '.$superQueryCount);
            }
        }

        if ($this->dumpsJson) {
            dump([
                'basic' => $basicResource,
                'super' => $superResource,
            ]);
        }

        $this->assertJsonStringEqualsJsonString($basicResource, $superResource);
        $this->assertLessThanOrEqual($basicQueryCount, $superQueryCount);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    abstract protected function produce(int $amount);
}
