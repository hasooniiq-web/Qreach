<?php

namespace App\Support;

/**
 * The six things a visitor can report. Nobody types anything: each reason has a fixed
 * sentence that becomes the first message of the conversation (web and app alike).
 */
final class ContactReasons
{
    /** @var array<string, string> reason key => the message the owner receives */
    public const MESSAGES = [
        'blocking' => 'سيارتك تعيق الطريق',
        'damage' => 'لاحظت ضررًا في المركبة',
        'malfunction' => 'يوجد عطل',
        'safety' => 'توجد مشكلة تتعلق بالسلامة',
        'assistance' => 'أحتاج إلى مساعدة',
        'lights' => 'أضواء المركبة مشتعلة',
    ];

    /** @var array<string, string> short labels, as the reason cards show them */
    public const SHORT = [
        'blocking' => 'سيارتك تعيق الطريق',
        'damage' => 'ضرر في المركبة',
        'malfunction' => 'يوجد عطل',
        'safety' => 'مشكلة سلامة',
        'assistance' => 'أحتاج مساعدة',
        'lights' => 'الأضواء مشتعلة',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::MESSAGES);
    }

    public static function message(string $reason): string
    {
        return self::MESSAGES[$reason] ?? '';
    }
}
