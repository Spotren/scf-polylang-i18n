<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class PolylangBridge
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function isAvailable(): bool
    {
        return function_exists('pll_current_language')
            || function_exists('pll_languages_list')
            || function_exists('pll__');
    }

    /** @return list<string> */
    public function languages(): array
    {
        $configured = $this->config->configuredRewriteLanguages();

        if ($configured !== []) {
            return $configured;
        }

        if (function_exists('pll_languages_list')) {
            $languages = pll_languages_list(['fields' => 'slug']);

            if (is_array($languages)) {
                return array_values(array_filter(array_map('sanitize_key', $languages)));
            }
        }

        $default = $this->defaultLanguage();

        return $default !== '' ? [$default] : [];
    }

    public function currentLanguage(): string
    {
        if (function_exists('pll_current_language')) {
            $language = pll_current_language('slug');

            if (is_string($language) && $language !== '') {
                return sanitize_key($language);
            }
        }

        return $this->defaultLanguage();
    }

    public function defaultLanguage(): string
    {
        if (function_exists('pll_default_language')) {
            $language = pll_default_language('slug');

            if (is_string($language) && $language !== '') {
                return sanitize_key($language);
            }
        }

        $configured = $this->config->configuredRewriteLanguages();

        return $configured[0] ?? '';
    }

    public function postLanguage(int $postId): string
    {
        if (function_exists('pll_get_post_language')) {
            $language = pll_get_post_language($postId, 'slug');

            if (is_string($language) && $language !== '') {
                return sanitize_key($language);
            }
        }

        return $this->currentLanguage();
    }

    public function termLanguage(int $termId): string
    {
        if (function_exists('pll_get_term_language')) {
            $language = pll_get_term_language($termId, 'slug');

            if (is_string($language) && $language !== '') {
                return sanitize_key($language);
            }
        }

        return $this->currentLanguage();
    }

    public function translate(string $text): string
    {
        if ($text === '' || !function_exists('pll__')) {
            return $text;
        }

        $translated = pll__($text);

        return is_string($translated) ? $translated : $text;
    }

    public function registerString(string $name, string $value, string $group): void
    {
        if ($value === '' || !function_exists('pll_register_string')) {
            return;
        }

        pll_register_string($name, $value, $group, false);
    }

    public function pathPrefix(string $language): string
    {
        if ($language === '') {
            return '';
        }

        if ($this->config->usesUnprefixedDefaultLanguage() && $language === $this->defaultLanguage()) {
            return '';
        }

        return $language;
    }
}
