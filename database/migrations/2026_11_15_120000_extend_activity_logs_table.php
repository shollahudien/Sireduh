<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->string('event', 100)->nullable()->index()->after('user_role');
            $table->text('description')->nullable()->after('event');
            $table->string('subject_type')->nullable()->after('description');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->string('subject_label')->nullable()->after('subject_id');
            $table->string('request_id', 64)->nullable()->index()->after('subject_label');
            $table->json('properties')->nullable()->after('status');
            $table->string('file_name')->nullable()->after('properties');
            $table->string('file_mime', 150)->nullable()->after('file_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_mime');
            $table->string('file_path', 1000)->nullable()->after('file_size');
            $table->string('file_hash', 128)->nullable()->after('file_path');
            $table->text('user_agent')->nullable()->change();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropIndex(['event', 'created_at']);
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropIndex(['event']);
            $table->dropIndex(['request_id']);
            $table->dropColumn([
                'event', 'description', 'subject_type', 'subject_id', 'subject_label',
                'request_id', 'properties', 'file_name', 'file_mime', 'file_size', 'file_path', 'file_hash',
            ]);
        });
    }
};
