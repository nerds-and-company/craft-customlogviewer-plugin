<?php

namespace Craft;

class Customlogviewer_CustomlogviewerController extends BaseController
{
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

        /** @var CustomLogViewerService $customLogViewerService */
        $customLogViewerService = craft()->customlogviewer;
        $logPath = craft()->path->getLogPath();


        $logFileNames = $customLogViewerService->fetchLogFiles($logPath);

        $currentLogFileName = $customLogViewerService->currentLogFile($variables, $logFileNames[0]);

        $contents = $this->getFileContents($currentLogFileName);
        $paginationVariable = new PaginateVariable();

        $contents = explode("\n", $contents);
        $contents = array_reverse($contents);

        $customLogViewerService->populatePaginationVariable($paginationVariable, $contents);

        $paginationRange = range($paginationVariable->first-1, $paginationVariable->last);

        $logEntries = $customLogViewerService->buildLogEntries($contents, $paginationRange);

        $this->renderTemplate('customlogviewer/index', array(
            'currentLogFileName'  => $currentLogFileName,
            'logEntries' => $logEntries,
            'logFileNames' => $logFileNames,
            'pageInfo' => $paginationVariable,
        ));
    }

    /**
     * @param $fileName
     *
     * @return array|bool|string
     *
     * @throws Exception
     */
    private function getFileContents($fileName)
    {
        $filePath = craft()->path->getLogPath() . $fileName;

        if (IOHelper::fileExists($filePath) === false) {
            $message = sprintf(
                'Requested logfile "%s" does not seem to exist',
                $fileName
            );
            throw new Exception($message);
        } else {
            return IOHelper::getFileContents($filePath);
        }
    }
}
