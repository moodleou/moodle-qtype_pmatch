<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Defines the testquestion_import_helper class.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the testquestion_import_helper class.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testquestion_import_helper {

    /** @var array Allow import file type. */
    const ACCEPTED_TYPES = ['csv', 'xlsx', 'html', 'json', 'ods'];
    /** @var int Minimum row require for import file. */
    const UPLOAD_FILE_MIN_ROW = 2;
    /** @var int Minimum column require for import file. */
    const UPLOAD_FILE_MIN_COL = 2;
    /** @var string Import type. */
    public $importtype = null;

    /**
     * testquestion_import_helper constructor.
     *
     * @param string $filepath Path to the file.
     */
    public function __construct($filepath) {
        $fileinfo = pathinfo($filepath);
        $this->importtype = $fileinfo['extension'];
    }

    /**
     * This creates an instance of the appropriate import, given the type of the file to be read
     *
     * @return qtype_pmatch_importer|qtype_pmatch_spout_importer Importer
     */
    public function import_factory() {
        $import = null;

        switch ($this->importtype) {
            case 'csv':
                $import = new qtype_pmatch_csv_importer();
                break;
            case 'xlsx':
                $import = new qtype_pmatch_xlsx_importer();
                break;
            case 'ods':
                $import = new qtype_pmatch_ods_importer();
                break;
            case 'json':
                $import = new qtype_pmatch_json_importer();
                break;
            case 'html':
                $import = new qtype_pmatch_html_importer();
                break;
            default:
                throw new \coding_exception('Invalid file type.');
        }

        return $import;
    }
}

/**
 * Question type: Pattern match: Import interface.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_pmatch_importer {

    /** @var string Content of the file */
    public $contents;

    /**
     * Prepares the reader to read the given file. It also makes sure
     * that the file exists and is readable.
     *
     * @param string $filepath Path to the file.
     */
    public function open($filepath) {
        if (!$this->contents = file_get_contents($filepath)) {
            throw new \coding_exception('Could not open testquestionresponses file.');
        }
    }

    /**
     * Get the responses from file
     *
     * @return array List of responses
     */
    public abstract function get_responses();

    /**
     * Validate the file
     *
     * @return array List of error if any.
     */
    public abstract function validate();

}

/**
 * Question type: Pattern match: Spout import interface.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_pmatch_spout_importer {

    /**
     * @var \Box\Spout\Reader\ReaderInterface $reader Spout Reader.
     */
    public $reader;

    /**
     * Prepares the reader to read the given file. It also makes sure
     * that the file exists and is readable.
     *
     * @param string $filepath Path to the file.
     */
    public function open($filepath) {
        $this->reader->open($filepath);
    }

    /**
     * Get the responses from file
     *
     * @return array List of responses
     */
    public function get_responses() {
        $responses = [];

        $row = 0;
        foreach ($this->reader->getSheetIterator() as $sheet) {
            // We only need the first sheet of the file.
            // Any more sheets in this file are note wanted.
            if ($sheet->getIndex() == 0) {
                foreach ($sheet->getRowIterator() as $data) {
                    $row += 1;
                    if ($row == 1) {
                        continue; // Skipping header row or comment.
                    }
                    $responses[] = $data->toArray();
                }
            }
            // Do not need to read more sheets.
            break;
        }

        $this->reader->close();

        return $responses;
    }

    /**
     * Validate the file
     *
     * @return array List of error if any.
     */
    public function validate() {
        $errcase = [
            'row' => true,
            'columnbigger' => false,
            'columnless' => false,
        ];

        $row = 0;
        foreach ($this->reader->getSheetIterator() as $sheet) {
            // We only need the first sheet of the file.
            // Any more sheets in this file are not wanted.
            if ($sheet->getIndex() == 0) {
                foreach ($sheet->getRowIterator() as $rowdata) {
                    $data = $rowdata->toArray();
                    $row++;
                    $columnno = count($data);
                    if ($columnno > testquestion_import_helper::UPLOAD_FILE_MIN_COL) {
                        $errcase['columnbigger'] = true;
                    } else if ($columnno < testquestion_import_helper::UPLOAD_FILE_MIN_COL) {
                        $errcase['columnless'] = true;
                        continue;
                    }
                    if (!is_numeric($data[0])) {
                        $score = null;
                    } else {
                        $score = (float) $data[0];
                    }
                    if (is_null($score) && empty($data[1])) {
                        // Ignore blank rows.
                        continue;
                    }
                    if ($row > testquestion_import_helper::UPLOAD_FILE_MIN_ROW) {
                        $errcase['row'] = false;
                        break;
                    }
                }
                break;
            }
        }

        $this->reader->close();

        return $errcase;
    }

}

/**
 * Question type: Pattern match: CSV Import class.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_csv_importer extends qtype_pmatch_spout_importer {

    /**
     * qtype_pmatch_csv_importer constructor.
     *
     */
    public function __construct() {
        $this->reader = \Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createCSVReader();
        $this->reader->setShouldPreserveEmptyRows(true);
    }
}

/**
 * Question type: Pattern match: XLSX Import class.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_xlsx_importer extends qtype_pmatch_spout_importer {

    /**
     * qtype_pmatch_xlsx_importer constructor.
     */
    public function __construct() {
        $this->reader = \Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createXLSXReader();
        $this->reader->setShouldPreserveEmptyRows(true);
    }
}

/**
 * Question type: Pattern match: ODS Import class.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_ods_importer extends qtype_pmatch_spout_importer {

    /**
     * qtype_pmatch_xlsx_importer constructor.
     */
    public function __construct() {
        $this->reader = \Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createODSReader();
        $this->reader->setShouldPreserveEmptyRows(true);
    }
}

/**
 * Question type: Pattern match: JSON Import class.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_json_importer extends qtype_pmatch_importer {

    /**
     * Get the responses from file
     *
     * @return array List of responses
     */
    public function get_responses() {
        $data = json_decode($this->contents);
        // We only need the first sheet of the file.
        // Any more sheets in this file are note wanted.
        return $data[0];
    }

    /**
     * Validate the file
     *
     * @return array List of error if any.
     */
    public function validate() {
        $errcase = [];

        if (empty(json_decode($this->contents))) {
            // File is empty.
            $errcase['row'] = true;
            $errcase['columnless'] = true;
            return $errcase;
        }

        // We only need the first sheet of the file.
        // Any more sheets in this file are not wanted.
        $repsonsedata = $this->get_responses();
        if (count($repsonsedata) <
                testquestion_import_helper::UPLOAD_FILE_MIN_ROW - 1) {
            // Json file does not include header row or comment.
            $errcase['row'] = true;
        }
        foreach ($repsonsedata as $content) {
            $counter = count($content);
            if ($counter === testquestion_import_helper::UPLOAD_FILE_MIN_COL) {
                continue;
            }
            if ($counter > testquestion_import_helper::UPLOAD_FILE_MIN_COL) {
                $errcase['columnbigger'] = true;
                break;
            } else {
                $errcase['columnless'] = true;
                break;
            }
        }

        return $errcase;
    }

}

/**
 * Question type: Pattern match: HTML Import class.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_html_importer extends qtype_pmatch_importer {

    /**
     * Get the responses from file
     *
     * @return array List of responses
     */
    public function get_responses() {
        $responses = [];

        $xml = new \DOMDocument();
        $xml->validateOnParse = true;
        $xml->loadHTML($this->contents);
        $datas = new \DOMXPath($xml);

        // We only need the first sheet of the file.
        // Any more sheets in this file are not wanted.
        $table = $datas->query("//table")->item(0);
        if (!empty($table)) {
            $rows = $table->getElementsByTagName("tr");
            if (!empty($rows->length)) {
                foreach ($rows as $key => $row) {
                    if ($key == 0) {
                        continue;
                    }
                    $cells = $row->getElementsByTagName('td');
                    $response = [];
                    foreach ($cells as $cell) {
                        $response[] = $cell->nodeValue;
                    }
                    $responses[] = $response;
                }
            }
        }

        return $responses;
    }

    /**
     * Validate the file
     *
     * @return array List of error if any.
     */
    public function validate() {
        $errcase = [];

        $xml = new \DOMDocument();
        $xml->validateOnParse = true;
        $xml->loadHTML($this->contents);
        $datas = new \DOMXPath($xml);
        $table = $datas->query("//table")->item(0);
        if (empty($table)) {
            $errcase['row'] = true;
            $errcase['columnless'] = true;
            return $errcase;
        }
        $rows = $table->getElementsByTagName("tr");
        if (empty($rows->length)) {
            $errcase['row'] = true;
            return $errcase;
        }
        if ($rows->length < testquestion_import_helper::UPLOAD_FILE_MIN_ROW) {
            $errcase['row'] = true;
        }

        foreach ($rows as $key => $row) {
            if ($key == 0) {
                continue;
            }
            $cells = $row->getElementsByTagName('td');
            if ($cells->length === testquestion_import_helper::UPLOAD_FILE_MIN_COL) {
                continue;
            }
            if ($cells->length > testquestion_import_helper::UPLOAD_FILE_MIN_COL) {
                $errcase['columnbigger'] = true;
                break;
            } else {
                $errcase['columnless'] = true;
                break;
            }
        }

        return $errcase;
    }

}
