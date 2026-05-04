<?php

declare(strict_types=1);

beforeEach(function (): void {
    config(['dymo-printer.templates_path' => __DIR__ . '/../../fixtures']);
});

it('serves a valid .dymo template file with the correct content type', function (): void {
    $this->get('/dymo-printer/templates/test-label.dymo')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8');
});

it('serves the correct XML content of the template', function (): void {
    $this->get('/dymo-printer/templates/test-label.dymo')
        ->assertSuccessful()
        ->assertSee('<DieCutLabel', false);
});

it('returns 404 for a non-.dymo file extension', function (): void {
    $this->get('/dymo-printer/templates/test-label.xml')
        ->assertNotFound();
});

it('returns 404 for a non-.dymo extension even if a matching file exists', function (): void {
    $this->get('/dymo-printer/templates/test-label.php')
        ->assertNotFound();
});

it('returns 404 when the requested template file does not exist', function (): void {
    $this->get('/dymo-printer/templates/missing-label.dymo')
        ->assertNotFound();
});

it('serves the dymo-system.js bundle with the correct content type', function (): void {
    $this->get('/dymo-printer/dymo-system.js')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/javascript');
});
