<?php

namespace leonverschuren\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use lsolesen\pel\PelEntryTime;
use lsolesen\pel\PelExif;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelInvalidArgumentException;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTag;
use lsolesen\pel\PelTiff;

/**
 * @author Leon Verschuren <contact@leonverschuren.nl>
 */
class ExifManager
{
    /** @var ArrayCollection */
    protected $filePaths;

    /** @var ArrayCollection */
    protected $noDate;

    /** @var PelJpeg */
    protected $prevWithDate;

    /** @var PelJpeg */
    protected $nextWithDate;

    /**
     * ExifManager constructor.
     */
    public function __construct()
    {
        $this->filePaths = new ArrayCollection();
        $this->noDate = new ArrayCollection();
    }

    public function writeMissingDates()
    {
        for ($i = 0; $i < $this->filePaths->count(); $i++) {
            $path = $this->filePaths->get($i);

            try {
                $jpeg = new PelJpeg($path);

                if ($this->hasDateTime($jpeg)) {
                    if ($this->noDate->isEmpty()) {
                        $this->prevWithDate = $jpeg;
                    } else {
                        $this->nextWithDate = $jpeg;
                        $this->writeDateTimes();
                        $this->noDate->clear();
                    }

                    print(sprintf("Skipped '%s'.\n", $path));
                } else {
                    $this->noDate->add($path);
                    print(sprintf("Added '%s' to queue.\n", $path));
                }
            } catch (PelInvalidArgumentException $e) {
                print(sprintf("File '%s' could not be read.\n", $path));
            }
        }
    }

    /**
     * @param PelJpeg $jpeg
     *
     * @return boolean
     */
    public function hasDateTime(PelJpeg $jpeg)
    {
        $exif = $jpeg->getExif();
        if ($exif == null) {
            return false;
        }

        $tiff = $exif->getTiff();
        $ifd0 = $tiff->getIfd();
        if ($ifd0 == null) {
            return false;
        }

        $ifd1 = $ifd0->getSubIfd(PelIfd::EXIF);
        if ($ifd1 == null) {
            return false;
        }

        $datetime = $ifd1->getEntry(PelTag::DATE_TIME_ORIGINAL);
        if ($datetime == null) {
            return false;
        }

        return true;
    }

    /**
     * @throws PelInvalidArgumentException
     */
    private function writeDateTimes()
    {
        $lower = $this->extractPelEntryTime($this->prevWithDate);
        $upper = $this->extractPelEntryTime($this->nextWithDate);

        $diff = $upper->getValue() - $lower->getValue();
        $step = ceil($diff / ($this->noDate->count() + 1));
        $base = $lower->getValue();

        for ($i = 0; $i < $this->noDate->count(); $i++) {
            $timestamp = $base + (($i + 1) * $step);
            $path = $this->noDate->get($i);
            $this->writePelEntryTime($path, $timestamp);
        }
    }

    /**
     * @param PelJpeg $jpeg
     *
     * @return PelEntryTime
     */
    private function extractPelEntryTime(PelJpeg $jpeg)
    {
        return $jpeg->getExif()->getTiff()->getIfd()->getSubIfd(PelIfd::EXIF)->getEntry(PelTag::DATE_TIME_ORIGINAL);
    }

    /**
     * @param $path
     * @param $timestamp
     *
     * @throws \lsolesen\pel\PelInvalidDataException
     */
    public function writePelEntryTime($path, $timestamp)
    {
        $jpeg = new PelJpeg($path);

        $exif = $jpeg->getExif();
        if ($exif == null) {
            $exif = new PelExif();
            $jpeg->setExif($exif);

            $tiff = new PelTiff();
            $exif->setTiff($tiff);
        } else {
            $tiff = $exif->getTiff();
        }

        $ifd0 = $tiff->getIfd();
        if ($ifd0 == null) {
            $ifd0 = new PelIfd(PelIfd::IFD0);
            $tiff->setIfd($ifd0);
        }

        $ifd1 = $ifd0->getSubIfd(PelIfd::EXIF);
        if ($ifd1 == null) {
            $sub_exif = new PelIfd(PelIfd::EXIF);
            $ifd0->addSubIfd($sub_exif);
            $ifd1 = $ifd0->getSubIfd(PelIfd::EXIF);
        }

        $datetime = new PelEntryTime(PelTag::DATE_TIME_ORIGINAL, $timestamp);
        $ifd1->addEntry($datetime);

        $jpeg->saveFile($path);

        print(sprintf("Calculated new DateTime for '%s'.\n", $path));
    }

    /**
     * @param $filePath
     *
     * @return $this
     */
    public function addFilePath($filePath)
    {
        $this->filePaths->add($filePath);

        return $this;
    }
}
