<?php

use UWDOEM\Framework\PickA\PickABuilder;
use UWDOEM\Framework\Section\SectionBuilder;


class PickATest extends PHPUnit_Framework_TestCase {

    public function testGetManifest() {
        $label1 = "l" . (string)rand();
        $label2 = "l" . (string)rand();

        $sections = [];
        for($i = 1; $i <= 3; $i++) {
            $sections["l" . (string)$i] = SectionBuilder::begin()
                ->setContent((string)rand())
                ->build();
        }

        $pickA = PickABuilder::begin()
            ->addLabel($label1)
            ->addWritables([
                "l1" => $sections["l1"],
                "l2" => $sections["l2"]
            ])
            ->addLabel($label2)
            ->addWritables([
                "l3" => $sections["l3"],
            ])
            ->build();

        $manifest = $pickA->getManifest();

        $this->assertContains($label1, array_keys($manifest));
        $this->assertContains($label2, array_keys($manifest));

        $this->assertContains("l1", array_keys($manifest));
        $this->assertContains("l2", array_keys($manifest));
        $this->assertContains("l3", array_keys($manifest));

        $this->assertContains($sections["l1"], array_values($manifest));
        $this->assertContains($sections["l2"], array_values($manifest));
        $this->assertContains($sections["l3"], array_values($manifest));
    }

    public function testGetLabels() {
        $label1 = "l" . (string)rand();
        $label2 = "l" . (string)rand();

        $sections = [];
        for($i = 1; $i <= 3; $i++) {
            $sections["l" . (string)$i] = SectionBuilder::begin()
                ->setContent((string)rand())
                ->build();
        }

        $pickA = PickABuilder::begin()
            ->addLabel($label1)
            ->addWritables([
                "l1" => $sections["l1"],
                "l2" => $sections["l2"]
            ])
            ->addLabel($label2)
            ->addWritables([
                "l3" => $sections["l3"],
            ])
            ->build();

        $labels = $pickA->getLabels();

        $this->assertContains($label1, $labels);
        $this->assertContains($label2, $labels);
    }

    public function testGetWritables() {
        $label1 = "l" . (string)rand();
        $label2 = "l" . (string)rand();

        $sections = [];
        for($i = 1; $i <= 3; $i++) {
            $sections["l" . (string)$i] = SectionBuilder::begin()
                ->setContent((string)rand())
                ->build();
        }

        $pickA = PickABuilder::begin()
            ->addLabel($label1)
            ->addWritables([
                "l1" => $sections["l1"],
                "l2" => $sections["l2"]
            ])
            ->addLabel($label2)
            ->addWritables([
                "l3" => $sections["l3"],
            ])
            ->build();

        $writables = $pickA->getWritables();

        $this->assertContains($sections["l1"], $writables);
        $this->assertContains($sections["l2"], $writables);
        $this->assertContains($sections["l3"], $writables);

    }


}
