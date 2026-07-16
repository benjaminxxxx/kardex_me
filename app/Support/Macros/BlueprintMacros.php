<?php
// app/Support/Macros/BlueprintMacros.php

use Illuminate\Database\Schema\Blueprint;

Blueprint::macro('auditColumns', function () {
    /** @var Blueprint $this */

    $this->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $this->string('created_by_name')->nullable(); // snapshot

    $this->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $this->string('updated_by_name')->nullable();

    $this->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
    $this->string('deleted_by_name')->nullable();
});

Blueprint::macro('dropAuditColumns', function () {
    /** @var Blueprint $this */

    $this->dropForeign(['created_by']);
    $this->dropColumn(['created_by', 'created_by_name']);

    $this->dropForeign(['updated_by']);
    $this->dropColumn(['updated_by', 'updated_by_name']);

    $this->dropForeign(['deleted_by']);
    $this->dropColumn(['deleted_by', 'deleted_by_name']);
});