<?php

declare(strict_types=1);

# -*- coding: utf-8 -*-
/**
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author   AuthorName <hello@inpsyde.com>
 * @license  GPLv2+
 * @link     https://www.inpsyde.com
 */

namespace Inpsyde\Wonolog\Tests\TestLogger;

/**
 * Used for testing purposes.
 *
 * It records all records and gives you access to them for verification.
 *
 * @method bool hasEmergency($record)
 * @method bool hasAlert($record)
 * @method bool hasCritical($record)
 * @method bool hasError($record)
 * @method bool hasWarning($record)
 * @method bool hasNotice($record)
 * @method bool hasInfo($record)
 * @method bool hasDebug($record)
 *
 * @method bool hasEmergencyRecords()
 * @method bool hasAlertRecords()
 * @method bool hasCriticalRecords()
 * @method bool hasErrorRecords()
 * @method bool hasWarningRecords()
 * @method bool hasNoticeRecords()
 * @method bool hasInfoRecords()
 * @method bool hasDebugRecords()
 *
 * @method bool hasEmergencyThatContains($message)
 * @method bool hasAlertThatContains($message)
 * @method bool hasCriticalThatContains($message)
 * @method bool hasErrorThatContains($message)
 * @method bool hasWarningThatContains($message)
 * @method bool hasNoticeThatContains($message)
 * @method bool hasInfoThatContains($message)
 * @method bool hasDebugThatContains($message)
 *
 * @method bool hasEmergencyThatMatches($message)
 * @method bool hasAlertThatMatches($message)
 * @method bool hasCriticalThatMatches($message)
 * @method bool hasErrorThatMatches($message)
 * @method bool hasWarningThatMatches($message)
 * @method bool hasNoticeThatMatches($message)
 * @method bool hasInfoThatMatches($message)
 * @method bool hasDebugThatMatches($message)
 *
 * @method bool hasEmergencyThatPasses($message)
 * @method bool hasAlertThatPasses($message)
 * @method bool hasCriticalThatPasses($message)
 * @method bool hasErrorThatPasses($message)
 * @method bool hasWarningThatPasses($message)
 * @method bool hasNoticeThatPasses($message)
 * @method bool hasInfoThatPasses($message)
 * @method bool hasDebugThatPasses($message)
 */
class TestLoggerV1 extends AbstractLoggerV1
{
    /**
     * @var array
     */
    // phpcs:ignore Inpsyde.CodeQuality.ForbiddenPublicProperty.Found
    public $records = [];

    // phpcs:ignore Inpsyde.CodeQuality.ForbiddenPublicProperty.Found
    public $recordsByLevel = [];

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = [])
    {
        $record = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $this->recordsByLevel[$record['level']][] = $record;
        $this->records[] = $record;
    }

    public function hasRecords($level)
    {
        return isset($this->recordsByLevel[$level]);
    }

    public function hasRecord($record, $level)
    {
        if (is_string($record)) {
            $record = ['message' => $record];
        }
        return $this->hasRecordThatPasses(static function ($rec) use ($record) {
            if ($rec['message'] !== $record['message']) {
                return false;
            }
            if (isset($record['context']) && $rec['context'] !== $record['context']) {
                return false;
            }
            return true;
        }, $level);
    }

    public function hasRecordThatContains($message, $level)
    {
        return $this->hasRecordThatPasses(static function ($rec) use ($message) {
            return strpos($rec['message'], $message) !== false;
        }, $level);
    }

    public function hasRecordThatMatches($regex, $level)
    {
        return $this->hasRecordThatPasses(static function ($rec) use ($regex) {
            return preg_match($regex, $rec['message']) > 0;
        }, $level);
    }

    public function hasRecordThatPasses(callable $predicate, $level)
    {
        if (!isset($this->recordsByLevel[$level])) {
            return false;
        }
        foreach ($this->recordsByLevel[$level] as $i => $rec) {
            // phpcs:ignore NeutronStandard.Functions.DisallowCallUserFunc.CallUserFunc
            if (call_user_func($predicate, $rec, $i)) {
                return true;
            }
        }
        return false;
    }

    public function __call($method, $args)
    {
        if (
            preg_match(
                '/(.*)(Debug|Info|Notice|Warning|Error|Critical|Alert|Emergency)(.*)/',
                $method,
                $matches
            ) > 0
        ) {
            $genericMethod = $matches[1]
                . ('Records' !== $matches[3] ? 'Record' : '')
                . $matches[3];
            $level = strtolower($matches[2]);
            if (method_exists($this, $genericMethod)) {
                $args[] = $level;
                // phpcs:ignore NeutronStandard.Functions.DisallowCallUserFunc.CallUserFunc
                return call_user_func_array([$this, $genericMethod], $args);
            }
        }
        throw new \BadMethodCallException(
            'Call to undefined method ' . get_class($this) . '::' . $method . '()'
        );
    }

    public function reset()
    {
        $this->records = [];
        $this->recordsByLevel = [];
    }
}
