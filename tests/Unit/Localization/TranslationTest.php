<?php

namespace Tests\Unit\Localization;

use Tests\TestCase;

class TranslationTest extends TestCase
{
    public function test_auth_messages_are_translated_to_german(): void
    {
        $en = __('auth.failed', [], 'en');
        $de = __('auth.failed', [], 'de');

        $this->assertNotSame('auth.failed', $de, 'German auth.failed translation is missing.');
        $this->assertNotSame($en, $de, 'German auth.failed should differ from English.');
    }

    public function test_watchlist_messages_are_translated_to_german(): void
    {
        $en = require lang_path('en/watchlist.php');
        $de = require lang_path('de/watchlist.php');

        $this->assertSame(array_keys($en), array_keys($de), 'en and de watchlist messages must define the same keys.');

        foreach (array_keys($en) as $key) {
            $this->assertNotSame($en[$key], $de[$key], "German watchlist.{$key} should differ from English.");
        }
    }

    public function test_validation_messages_are_translated_to_german(): void
    {
        $en = __('validation.required', ['attribute' => 'email'], 'en');
        $de = __('validation.required', ['attribute' => 'email'], 'de');

        $this->assertNotSame('validation.required', $de, 'German validation.required translation is missing.');
        $this->assertNotSame($en, $de, 'German validation.required should differ from English.');
    }
}
