<?php

declare (strict_types = 1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once "pathDefinitions.php";

class RecordingAPI
{
    private string $recordingFile;

    public function __construct($recordingFile = RECORDING_CONF_FILE)
    {
        if ($recordingFile instanceof SimpleXMLElement) {
            $this->recordingFile = (string) $recordingFile;
        } elseif (is_string($recordingFile)) {
            $this->recordingFile = $recordingFile;
        } else {
            throw new InvalidArgumentException('Invalid recording file path');
        }
    }

    public function handleRequest(): void
    {
        try {
            $action = $_GET['action'] ?? '';
            $method = $_SERVER['REQUEST_METHOD'];

            switch ($method) {
                case 'GET':
                    if ($action === 'getdrive') {
                        $this->getRecordingDrive();
                    } elseif ($action === 'view') {
                        $this->viewSchedule();
                    } elseif ($action === 'getdrivelist') {
                        $this->getDriveListAction();
                    } else {
                        $this->sendResponse(['error' => 'Invalid action'], 400);
                    }
                    break;
                case 'POST':
                    if ($action === 'add') {
                        $this->addRecording();
                    } elseif ($action === 'setdrive') {
                        $this->setRecordingDrive();
                    } else {
                        $this->sendResponse(['error' => 'Invalid action'], 400);
                    }
                    break;
                case 'DELETE':
                    if ($action === 'delete') {
                        $this->deleteEvent();
                    } else {
                        $this->sendResponse(['error' => 'Invalid action'], 400);
                    }
                    break;
                default:
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
            }
        } catch (Exception $e) {
            $this->sendResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function getDriveListAction(): void
    {
        $driveList = $this->getDriveList();
        $this->sendResponse(['drives' => $driveList]);
    }

    private function getDriveList(): array
    {
        $driveArr = [];

        $fso = new COM('Scripting.FileSystemObject');
        $D = $fso->Drives;

        foreach ($D as $d) {
            if ($d->IsReady) {
                $dO = $fso->GetDrive($d);
                $s = "";

                if ($dO->DriveType == 3) {
                    $n = $dO->ShareName;
                } else {
                    $n = $dO->VolumeName;
                    $s = $this->bytesToSize($dO->FreeSpace);
                }

                $driveArr[] = [
                    'letter' => $dO->DriveLetter,
                    'name' => $n,
                    'freeSpace' => $s,
                ];
            }
        }

        return $driveArr;
    }

    private function getRecordingDrive(): void
    {
        $recordingXML = $this->loadXMLFile();
        $drive = $recordingXML->Drive['name'] ?? '';
        $this->sendResponse(['drive' => (string) $drive]);
    }

    private function setRecordingDrive(): void
    {
        $data = $this->getJsonInput();

        if (!isset($data['drive'])) {
            $this->sendResponse(['error' => 'Drive name is required'], 400);
            return;
        }

        $recordingXML = $this->loadXMLFile();
        $recordingXML->Drive['name'] = $data['drive'];
        $this->saveXMLFile($recordingXML);
        $this->sendResponse(['message' => 'Recording drive set successfully']);
    }

    private function viewSchedule(): void
    {
        $recordingXML = $this->loadXMLFile();
        $recordings = [];

        foreach ($recordingXML->Recording as $recording) {
            $recordings[] = $this->formatRecording($recording);
        }

        $this->sendResponse(['recordings' => $recordings]);
    }

    private function addRecording(): void
    {
        $data = $this->getJsonInput();
        $required = ['cam', 'startDay', 'startTime', 'fps', 'timestamp', 'duration'];

        if (!$this->validateInput($data, $required)) {
            $this->sendResponse(['error' => 'Missing required fields'], 400);
            return;
        }

        $recordingXML = $this->loadXMLFile();
        $recording = $recordingXML->addChild("Recording");

        foreach ($required as $field) {
            $recording->addAttribute($field, $data[$field]);
        }

        $this->saveXMLFile($recordingXML);
        $this->sendResponse(['message' => 'Recording added successfully']);
    }

    private function deleteEvent(): void
    {
        $data = $this->getJsonInput();
        $required = ['cam', 'startDay', 'duration'];

        if (!$this->validateInput($data, $required)) {
            $this->sendResponse(['error' => 'Missing required fields'], 400);
            return;
        }

        $recordingXML = $this->loadXMLFile();
        $deleted = false;

        foreach ($recordingXML->Recording as $index => $recording) {
            if ($this->matchesRecording($recording, $data)) {
                unset($recordingXML->Recording[$index]);
                $deleted = true;
                break;
            }
        }

        if ($deleted) {
            $this->saveXMLFile($recordingXML);
            $this->sendResponse(['message' => 'Recording deleted successfully']);
        } else {
            $this->sendResponse(['error' => 'Recording not found'], 404);
        }
    }

    private function loadXMLFile(): SimpleXMLElement
    {
        if (!file_exists($this->recordingFile)) {
            $xml = new SimpleXMLElement('<?xml version="1.0"?><RecordingSchedule><Drive name="" /></RecordingSchedule>');
            $this->saveXMLFile($xml);
            return $xml;
        }
        $xml = @simplexml_load_file($this->recordingFile);
        if ($xml === false) {
            throw new Exception("Failed to load XML file: " . $this->recordingFile);
        }
        return $xml;
    }

    private function saveXMLFile(SimpleXMLElement $xml): void
    {
        if (file_put_contents($this->recordingFile, $xml->asXML()) === false) {
            throw new Exception("Failed to save XML file: " . $this->recordingFile);
        }
    }

    private function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON input: " . json_last_error_msg());
        }
        return $data;
    }

    private function validateInput(array $data, array $required): bool
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

    private function matchesRecording(SimpleXMLElement $recording, array $data): bool
    {
        return (string) $recording['camera'] === $data['cam'] &&
        (string) $recording['startDay'] === $data['startDay'] &&
        (string) $recording['durationSecs'] === $data['duration'];
    }

    private function formatRecording(SimpleXMLElement $recording): array
    {
        $startTime = strtotime((string) $recording['startTime']);
        $duration = (int) $recording['durationSecs'];
        $endTime = $startTime + $duration;

        return [
            'startDay' => (string) $recording['startDay'],
            'times' => date('h:i A', $startTime) . ' - ' . date('h:i A', $endTime),
            'camera' => (string) $recording['camera'],
            'timestamp' => (string) $recording['timestamp'],
            'fps' => $this->frameRateToHRF((float) $recording['FPS']),
            'size' => $this->bytesToSize(20000 * (float) $recording['FPS'] * $duration),
        ];
    }

    private function frameRateToHRF(float $rate): string
    {
        $rates = [
            10 => "Normal",
            1 => "Once a Second",
            0.2 => "Every 5 Seconds",
            0.1 => "Every 10 Seconds",
            0.033333 => "Every 30 Seconds",
            0.016667 => "Every Minute",
            0.003333 => "Every 5 Minutes",
        ];

        return $rates[$rate] ?? "Custom";
    }

    private function bytesToSize(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function sendResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Create and run the API
$api = new RecordingAPI(RECORDING_CONF_FILE);
$api->handleRequest();
