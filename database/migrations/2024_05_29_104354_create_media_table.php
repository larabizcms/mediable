<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LarabizCMS\Mediable\Models\Media;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'media',
            function (Blueprint $table) {
                $table->id();
                $table->string('disk', 20)->index()->default('public');
                $table->nullableMorphs('uploaded_by');
                $table->string('name');
                $table->string('type', 5)->index()->default(Media::TYPE_FILE);
                $table->string('path', 190);
                $table->string('mime_type', 30)->nullable();
                $table->string('extension', 10)->nullable();
                $table->string('image_size', 20)->nullable();
                $table->bigInteger('size')->default(0);
                $table->json('conversions')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('parent_id')->index()->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('parent_id')
                    ->on('media')
                    ->references('id');
            }
        );

        Schema::create(
            'mediable',
            function (Blueprint $table) {
                $table->primary(['media_id', 'mediable_id', 'mediable_type', 'channel']);
                $table->unsignedBigInteger('media_id')->index();
                $table->morphs('mediable');
                $table->string('channel', 50)->index()->default('default');
                $table->timestamps();

                $table->foreign('media_id')
                    ->on('media')
                    ->references('id')
                    ->onDelete('cascade');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mediable');
        Schema::dropIfExists('media');
    }
};
