<?php

namespace Tests\Feature\Entries;

use Facades\Tests\Factories\EntryFactory;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Entries\Collection;
use Statamic\Facades\Access;
use Statamic\Facades\User;
use Tests\Auth\Access\Fakes\FakeApplyToEntryQueryRule;
use Tests\FakesRoles;
use Tests\PreventSavingStacheItemsToDisk;
use Tests\TestCase;

class ViewEntryListingTest extends TestCase
{
    use FakesRoles;
    use PreventSavingStacheItemsToDisk;

    #[Test]
    public function it_shows_entries_index()
    {
        $this->setTestRoles(['test' => [
            'access cp',
            'view test entries',
        ]]);

        $user = tap(User::make()->assignRole('test'))->save();

        $collection = tap(Collection::make('test'))->save();

        foreach (['one', 'two', 'three'] as $handle) {
            EntryFactory::collection($collection)
                ->slug($handle)
                ->create();
        }

        $response = $this
            ->actingAs($user)
            ->get(cp_route('collections.entries.index', ['collection' => 'test']))
            ->assertOk();

        $entries = collect($response->getData()->data);

        $this->assertEquals(['one', 'two', 'three'], $entries->pluck('slug')->all());
    }

    #[Test]
    public function it_shows_only_entries_in_index_for_sites_user_can_access()
    {
        $this->setSites([
            'en' => ['url' => '/', 'locale' => 'en_US', 'name' => 'English'],
            'fr' => ['url' => '/', 'locale' => 'fr_FR', 'name' => 'French'],
            'de' => ['url' => '/', 'locale' => 'de_DE', 'name' => 'German'],
        ]);

        $collection = tap(Collection::make('test'))->save();

        foreach (['en', 'fr', 'de'] as $locale) {
            foreach (['one', 'two', 'three'] as $handle) {
                EntryFactory::collection($collection)
                    ->slug("{$locale}-{$handle}")
                    ->locale($locale)
                    ->create();
            }
        }

        $this->setTestRoles(['test' => [
            'access cp',
            'view test entries',
            'access en site',
            'access de site',
        ]]);

        $user = tap(User::make()->assignRole('test'))->save();

        $response = $this
            ->actingAs($user)
            ->get(cp_route('collections.entries.index', ['collection' => 'test']))
            ->assertOk();

        $entries = collect($response->getData()->data);

        $expected = [
            'en-one',
            'en-two',
            'en-three',
            'de-one',
            'de-two',
            'de-three',
        ];

        $this->assertEquals($expected, $entries->pluck('slug')->all());
    }

    #[Test]
    public function it_restricts_entries_by_access_rules()
    {
        Access::register(FakeApplyToEntryQueryRule::class);

        $this->setTestRoles(['test' => [
            'access cp',
            'view test entries',
        ]]);

        $user = tap(User::make()->assignRole('test'))->save();
        $collection = tap(Collection::make('test'))->save();

        EntryFactory::collection($collection)->slug('visible')->create();
        EntryFactory::collection($collection)->slug('secret')->create();

        $response = $this
            ->actingAs($user)
            ->get(cp_route('collections.entries.index', ['collection' => 'test']))
            ->assertOk();

        $this->assertSame(['visible'], collect($response->getData()->data)->pluck('slug')->all());
    }
}
