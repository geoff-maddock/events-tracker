<?php

namespace Tests\Unit\Services\SessionStore;

use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\TestCase;

class ListParameterSessionStoreTest extends TestCase
{
    private Store $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new Store('test', new ArraySessionHandler(60));
        $this->session->start();
    }

    private function makeStore(string $base = 'events', string $prefix = 'events.upcoming'): ListParameterSessionStore
    {
        $store = new ListParameterSessionStore($this->session);

        return $store->setBaseIndex($base)->setKeyPrefix($prefix);
    }

    public function test_save_then_load_roundtrips_all_parameters(): void
    {
        $store = $this->makeStore();
        $store->setFilters(['name' => 'foo'])
            ->setSortDirection('asc')
            ->setSortFieldName('start_at')
            ->setLimit(25)
            ->setIndexTab('upcoming')
            ->setIsEmptyFilter(false)
            ->save();

        // Create a new instance to force a session reload via setKeyPrefix
        $reloaded = $this->makeStore();

        $this->assertSame(['name' => 'foo'], $reloaded->getFilters());
        $this->assertSame('asc', $reloaded->getSortDirection());
        $this->assertSame('start_at', $reloaded->getSortFieldName());
        $this->assertSame(25, $reloaded->getLimit());
        $this->assertSame('upcoming', $reloaded->getIndexTab());
        $this->assertFalse($reloaded->getIsEmptyFilter());
    }

    public function test_clear_filter_resets_only_filter_keys(): void
    {
        $store = $this->makeStore();
        $store->setFilters(['city' => 'pgh'])
            ->setSortDirection('desc')
            ->setSortFieldName('name')
            ->save();

        $store->clearFilter();

        $reloaded = $this->makeStore();
        $this->assertNull($reloaded->getFilters());
        // Sort survives
        $this->assertSame('desc', $reloaded->getSortDirection());
        $this->assertSame('name', $reloaded->getSortFieldName());
    }

    public function test_clear_sort_resets_only_sort_and_limit(): void
    {
        $store = $this->makeStore();
        $store->setFilters(['city' => 'pgh'])
            ->setSortDirection('desc')
            ->setSortFieldName('name')
            ->setLimit(50)
            ->save();

        $store->clearSort();

        $reloaded = $this->makeStore();
        $this->assertSame(['city' => 'pgh'], $reloaded->getFilters());
        $this->assertNull($reloaded->getSortDirection());
        $this->assertNull($reloaded->getSortFieldName());
        $this->assertNull($reloaded->getLimit());
    }

    public function test_is_empty_filter_true_is_persisted(): void
    {
        $store = $this->makeStore();
        $store->setIsEmptyFilter(true)->save();

        $this->assertTrue($this->makeStore()->getIsEmptyFilter());
    }

    public function test_default_is_empty_filter_is_false_when_unset(): void
    {
        $store = $this->makeStore();
        $this->assertFalse($store->getIsEmptyFilter());
    }
}
