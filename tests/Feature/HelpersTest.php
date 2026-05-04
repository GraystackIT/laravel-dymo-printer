<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\Facades\DymoPrinter;

it('dymo_printer() returns null when no printer is set', function (): void {
    expect(dymo_printer())->toBeNull();
});

it('dymo_printer() returns the currently selected printer name', function (): void {
    DymoPrinter::set('DYMO LabelWriter 450');

    expect(dymo_printer())->toBe('DYMO LabelWriter 450');
});

it('dymo_has_printer() returns false when no printer is set', function (): void {
    expect(dymo_has_printer())->toBeFalse();
});

it('dymo_has_printer() returns true after a printer is set', function (): void {
    DymoPrinter::set('DYMO LabelWriter 450');

    expect(dymo_has_printer())->toBeTrue();
});

it('dymo_has_printer() returns false after the selection is cleared', function (): void {
    DymoPrinter::set('DYMO LabelWriter 450');
    DymoPrinter::clear();

    expect(dymo_has_printer())->toBeFalse();
});

it('dymo_template_url() generates a URL containing the template filename', function (): void {
    $url = dymo_template_url('shipping-label.dymo');

    expect($url)->toContain('/dymo-printer/templates/shipping-label.dymo');
});

it('dymo_template_url() strips directory traversal sequences from the filename', function (): void {
    $url = dymo_template_url('../../../etc/passwd.dymo');

    expect($url)->toContain('passwd.dymo')
        ->and($url)->not->toContain('..');
});
