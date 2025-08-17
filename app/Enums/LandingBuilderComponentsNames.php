<?php

namespace App\Enums;

use ReflectionClass;

class LandingBuilderComponentsNames
{

    // Names
    const TWO_COLUMNS_HERO = 'two_columns_hero';
    const STATISTICS = 'statistics';
    const FEATURED_COURSES = 'featured_courses';
    const TRENDING_CATEGORIES = 'trending_categories';
    const NEWEST_COURSES = 'newest_courses';
    const BEST_SELLING_COURSES = 'best_selling_courses';
    const BEST_RATED_COURSES = 'best_rated_courses';
    const DISCOUNTED_COURSES = 'discounted_courses';
    const FREE_COURSES = 'free_courses';
    const INSTRUCTORS = 'instructors';
    const BLOG = 'blog';
    const VERTICAL_SPACER = 'vertical_spacer';


    const categories = [
        self::TWO_COLUMNS_HERO => LandingBuilderComponentCategories::HERO,
        self::STATISTICS => LandingBuilderComponentCategories::STATISTICS,
        self::FEATURED_COURSES => LandingBuilderComponentCategories::COURSES,
        self::TRENDING_CATEGORIES => LandingBuilderComponentCategories::CATEGORIES,
        self::NEWEST_COURSES => LandingBuilderComponentCategories::COURSES,
        self::BEST_SELLING_COURSES => LandingBuilderComponentCategories::COURSES,
        self::BEST_RATED_COURSES => LandingBuilderComponentCategories::COURSES,
        self::DISCOUNTED_COURSES => LandingBuilderComponentCategories::COURSES,
        self::FREE_COURSES => LandingBuilderComponentCategories::COURSES,
        self::INSTRUCTORS => LandingBuilderComponentCategories::INSTRUCTORS,
        self::BLOG => LandingBuilderComponentCategories::BLOG_POSTS,
        self::VERTICAL_SPACER => LandingBuilderComponentCategories::MISCELLANEOUS,
    ];

    /**
     * Returns all constants as an array.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return array_keys(self::categories);
    }

    public static function getCategory(string $name): string
    {
        return self::categories[$name] ?? LandingBuilderComponentCategories::HERO;
    }
}
