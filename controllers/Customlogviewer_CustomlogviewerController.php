<?php

namespace Craft;

class Customlogviewer_CustomlogviewerController extends BaseController
{
    const PSR_LOG_REGEX = '/^\[(?P<DATETIME>[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2})\] (?P<CHANNEL>.*)\.(?P<LEVEL>[A-Z]+):(?P<MESSAGE>.*)/';
    const PHP_ERROR_LOG_REGEX = '/^\[(?P<DATETIME>[\d]{2}-[a-zA-Z]{3}-[\d]{4}\s[\d]{2}:[\d]{2}:[\d]{2} [A-Z]*)\] (?P<CHANNEL>PHP)\s(?P<LEVEL>.*):\s(?P<MESSAGE>.*)/';
    const PHP_ERROR_STACK_REGEX = '/\[(?P<DATETIME>[\d]{2}-[a-zA-Z]{3}-[\d]{4}\s[\d]{2}:[\d]{2}:[\d]{2} [A-Z]{2,3})\]\s(?P<CHANNEL>PHP)\s+(?P<LEVEL>(?:[0-9]{1,2}\.)\s|(?:Stack trace))(?P<MESSAGE>.*)/';

    /**
     * @throws HttpException
     */
    public function init()
    {
        craft()->userSession->requireAdmin();
    }

    public function actionIndex(array $variables = array())
    {
        craft()->config->maxPowerCaptain();

        $logFileNames = $this->fetchLogFiles();
        $currentLogFileName = $this->currentLogFile($variables, $logFileNames);
        $currentLogPath = craft()->path->getLogPath() . $currentLogFileName;
        $this->validateLogFileExists($currentLogPath);

        $contents = IOHelper::getFileContents($currentLogPath);
        $contents = explode("\n", $contents);

        $contents = array_reverse($contents);

        $paginationVariable = $this->paginationVariable($contents);

        $paginationRange = range($paginationVariable->first-1, $paginationVariable->last);

        $logEntries = $this->buildLogEntries($contents, $paginationRange);

        $this->renderTemplate('customlogviewer/index', array(
            'currentLogFileName'  => $currentLogFileName,
            'logEntries' => $logEntries,
            'logFileNames' => $logFileNames,
            'pageInfo' => $paginationVariable,
        ));
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    private function fetchLogFiles()
    {
        $logFileNames = [];

        $logFolderContents = IOHelper::getFolderContents(craft()->path->getLogPath());

        if ($logFolderContents === false) {
            throw new Exception('Could not get log folder contents');
        } else {
            foreach ($logFolderContents as $index => $filePath) {
                if ($this->shouldFileBeIncluded($filePath)) {
                    $logFileNames[] = IOHelper::getFileName($filePath);
                }
            }
        }
        return $logFileNames;
    }

    /**
     * @param string $currentFilePath
     *
     * @throws Exception
     */
    private function validateLogFileExists($currentFilePath)
    {
        if (IOHelper::fileExists($currentFilePath) === false) {
            $message = sprintf(
                'Requested logfile "%s" does not seem to exist',
                IOHelper::getFileName($currentFilePath)
            );
            throw new Exception($message);
        }
    }

    /**
     * @param string[] $contents
     * @param int[] $paginationRange
     *
     * @return array
     */
    private function buildLogEntries(array $contents, array $paginationRange)
    {
        $logEntries = [];

        $found = false;

        foreach ($contents as $index => $line) {

            $matches = [];

            if (in_array($index, $paginationRange, false) === false) {
                if ($found === false) {
                    continue;
                } else {
                    break;
                }
            } else {
                $found = true;
                if (preg_match(self::PSR_LOG_REGEX, $line, $matches) === 1) {
                    /* PSR compatible logger */
                    $logEntry = $this->buildEntry($matches);
                //} else if (preg_match(self::PHP_ERROR_LOG_REGEX, $line, $matches) === 1) {
                //    /* PHP Error Log */
                //    $logEntry = $this->buildEntry($matches);
                //} else if (preg_match(self::PHP_ERROR_STACK_REGEX, $line, $matches) === 1) {
                //    /* PHP Error Log Stack Trace */
                //    if ($matches['LEVEL'] !== 'Stack trace') {
                //        /** @var Customlogviewer_CustomLogEntryModel $entry */
                //        $previousIndex = count($logEntries) - 1;
                //        if (isset($logEntries[$previousIndex])) {
                //            $entry = $logEntries[$previousIndex][0];
                //            $attribute = $entry->getAttribute('stacktrace');
                //            if (is_array($attribute) === false) {
                //                $attribute = [];
                //            }
                //            array_push($attribute, $matches['MESSAGE']);
                //
                //            $entry->setAttribute('stacktrace', $attribute);
                //        }
                //    }
                } elseif(trim($line) !== '') {
                    $logEntry = $this->buildEntry($line);
                }

                if (isset($logEntry)) {
                    array_push($logEntries, array($logEntry));
                }
            }
        }

        return $logEntries;
    }

    /**
     * @param array $variables
     *
     * @return string
     */
    private function currentLogFile(array $variables, array $logFileNames)
    {
        return isset($variables['currentLogFileName']) ? (string) $variables['currentLogFileName'] : $logFileNames[0];
    }

    /**
     * @param string|array $entry
     *
     * @return LogEntryModel
     */
    private function buildEntry($entry)
    {
        $logEntry = new Customlogviewer_CustomLogEntryModel();

        $title = '';

        if (is_array($entry)) {
            $message = $entry['MESSAGE'];
            $dateTime = new DateTime($entry['DATETIME']);

            $logEntry->setAttribute('dateTime', $dateTime);
            $title = $logEntry->getAttribute('dateTime')->localeDate() . ' ' . $logEntry->getAttribute('dateTime')->localeTime();
            $logEntry->setAttribute('channel', $entry['CHANNEL']);
            $logEntry->setAttribute('level', $entry['LEVEL']);
        } else {
            $message = (string)$entry;
            $logEntry->setAttribute('channel', '-');
            $logEntry->setAttribute('dateTime', '-');
            $logEntry->setAttribute('level', '-');
        }

        $logEntry->setAttribute('message', $message);
        $logEntry->setAttribute('title', $title);

        return $logEntry;
    }

    /**
     * @param array $contents
     *
     * @return PaginateVariable
     */
    private function paginationVariable($contents)
    {
        $limit = 10;
        $currentPage = craft()->request->getPageNum();

        $totalEntries = count($contents);
        $totalPages = (int) ceil($totalEntries/$limit);

        $paginateVariable = new PaginateVariable();

        $offset = $limit * ($currentPage - 1);

        $last = $offset + $limit;

        $paginateVariable->first = $offset + 1;
        $paginateVariable->last = $last;
        $paginateVariable->total = $totalPages;
        $paginateVariable->currentPage = $currentPage;
        $paginateVariable->totalPages = $totalPages;

        return $paginateVariable;
    }

    /**
     * @param $filePath
     *
     * @return bool
     */
    private function shouldFileBeIncluded($filePath)
    {
        $fileName = IOHelper::getFileName($filePath);
        $craftFileName = 'craft.log';

        return IOHelper::fileExists($filePath) && substr($fileName, 0, strlen($craftFileName)) !== $craftFileName
        && $fileName !== 'phperrors.log';
    }
}
