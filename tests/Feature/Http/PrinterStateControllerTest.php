<?php

declare(strict_types=1);

it('GET /dymo-printer/printer returns null when no printer is set', function (): void {
    $this->getJson('/dymo-printer/printer')
        ->assertSuccessful()
        ->assertJson([
            'printer'     => null,
            'has_printer' => false,
        ]);
});

it('POST /dymo-printer/printer stores the printer selection', function (): void {
    $this->postJson('/dymo-printer/printer', ['printer_name' => 'DYMO LabelWriter 450'])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'printer' => 'DYMO LabelWriter 450',
        ]);
});

it('GET /dymo-printer/printer returns the stored printer after saving', function (): void {
    $this->postJson('/dymo-printer/printer', ['printer_name' => 'DYMO LabelWriter 450']);

    $this->getJson('/dymo-printer/printer')
        ->assertSuccessful()
        ->assertJson([
            'printer'     => 'DYMO LabelWriter 450',
            'has_printer' => true,
        ]);
});

it('DELETE /dymo-printer/printer clears the printer selection', function (): void {
    $this->postJson('/dymo-printer/printer', ['printer_name' => 'DYMO LabelWriter 450']);

    $this->deleteJson('/dymo-printer/printer')
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $this->getJson('/dymo-printer/printer')
        ->assertSuccessful()
        ->assertJson([
            'printer'     => null,
            'has_printer' => false,
        ]);
});

it('POST /dymo-printer/printer returns 422 when printer_name is missing', function (): void {
    $this->postJson('/dymo-printer/printer', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['printer_name']);
});

it('POST /dymo-printer/printer returns 422 when printer_name exceeds 255 characters', function (): void {
    $this->postJson('/dymo-printer/printer', ['printer_name' => str_repeat('a', 256)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['printer_name']);
});

it('POST /dymo-printer/printer accepts a printer_name of exactly 255 characters', function (): void {
    $this->postJson('/dymo-printer/printer', ['printer_name' => str_repeat('a', 255)])
        ->assertSuccessful();
});
