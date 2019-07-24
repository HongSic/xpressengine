<?php
/**
 * UserMigration.php
 *
 * PHP version 7
 *
 * @category    Migrations
 * @package     Xpressengine\Migrations
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Migrations;

use Illuminate\Database\Schema\Blueprint;
use DB;
use Schema;
use Xpressengine\Support\Migration;

/**
 * Class UserMigration
 *
 * @category    Migrations
 * @package     Xpressengine\Migrations
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class UserMigration extends Migration
{
    /**
     * Run when install the application.
     *
     * @return void
     */
    public function install()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->string('id', 36)->comment('user ID');
            $table->string('display_name', 255)->unique()->comment('display name.');
            $table->string('email', 255)->nullable()->comment('email');
            $table->string('password', 255)->nullable()->comment('password');
            $table->string('rating', 15)->default('user')->comment('user rating. guest/user/manager/super');
            $table->string('status', 20)->comment('account status. activated/deactivated');
            $table->text('introduction')->default(null)->nullable()->comment('user introduction');
            $table->string('profile_image_id', 36)->nullable()->comment('profile image file ID');
            $table->string('remember_token', 255)->nullable()->comment('token for keep login');
            $table->timestamp('login_at')->nullable()->comment('login date');
            $table->timestamp('created_at')->nullable()->index()->comment('created date');
            $table->timestamp('updated_at')->nullable()->index()->comment('updated date');
            $table->timestamp('password_updated_at')->nullable()->comment('password updated date');

            $table->primary('id');
        });
        Schema::create('user_group', function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->string('id', 36)->comment('group ID');
            $table->string('name')->comment('group name');
            $table->string('description', 1000)->comment('group description');
            $table->integer('order')->default(0)->index()->comment('order number');
            $table->timestamp('created_at')->nullable()->index()->comment('created date');
            $table->timestamp('updated_at')->nullable()->comment('updated date');

            $table->primary('id');
        });

        Schema::create('user_group_user', function (Blueprint $table) {
            // user IDs included in the use group
            $table->engine = "InnoDB";

            $table->increments('id')->comment('ID');
            $table->string('group_id', 36)->comment('group ID');
            $table->string('user_id', 36)->comment('user ID');
            $table->timestamp('created_at')->nullable()->comment('created date');

            $table->unique(['group_id', 'user_id']);
            $table->index('group_id');
            $table->index('user_id');
        });

        Schema::create('user_account', function (Blueprint $table) {
            // user account. Login via account information provided by other providers. As like OAuth.
            $table->engine = "InnoDB";

            $table->string('id', 36)->comment('ID');
            $table->string('user_id')->comment('user ID');
            $table->string('account_id')->comment('account Id');
            $table->string('email')->nullable()->comment('email');
            $table->char('provider', 20)->comment('OAuth provider. naver/twitter/facebook/...');
            $table->string('token', 500)->comment('token');
            $table->string('token_secret', 500)->comment('token secret');
            $table->timestamp('created_at')->nullable()->comment('created date');
            $table->timestamp('updated_at')->nullable()->comment('updated date');

            $table->primary('id');
            $table->unique(['provider', 'account_id']);
        });

        Schema::create('user_email', function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->increments('id')->comment('ID');
            $table->string('user_id', 36)->comment('user ID');
            $table->string('address')->comment('email address');
            $table->timestamp('created_at')->nullable()->index()->comment('created date');
            $table->timestamp('updated_at')->nullable()->comment('updated date');

            $table->index('user_id');
            $table->index('address');
        });

        Schema::create('user_pending_email', function (Blueprint $table) {
            // email confirm
            $table->engine = "InnoDB";

            $table->increments('id')->comment('ID');
            $table->string('user_id', 36)->comment('user ID');
            $table->string('address')->comment('email address');
            $table->string('confirmation_code')->nullable()->comment('confirmation code');
            $table->timestamp('created_at')->nullable()->index()->comment('created date');
            $table->timestamp('updated_at')->nullable()->comment('updated date');

            $table->index('user_id');
            $table->index('address');
        });

        Schema::create('user_password_resets', function (Blueprint $table) {
            // find account password
            $table->engine = "InnoDB";

            $table->increments('id')->comment('ID');
            $table->string('email')->index()->comment('email address');
            $table->string('token')->index()->comment('token');
            $table->timestamp('created_at')->nullable()->comment('created date');
        });

        Schema::create('user_register_token', function (Blueprint $table) {
            // find account password
            $table->engine = "InnoDB";

            $table->string('id', 36)->comment('user ID');
            $table->string('guard', 100)->comment('the guard creating token');
            $table->text('data')->comment('token data');
            $table->timestamp('created_at')->nullable()->comment('created date');
        });

        Schema::create('user_terms', function (Blueprint $table) {
            $table->string('id', 36);
            $table->string('title');
            $table->string('content')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_enabled')->default(false);

            $table->primary('id');
            $table->engine = "InnoDB";
        });

        Schema::create('user_login_log', function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->bigIncrements('id')->comment('row id');
            $table->string('user_id', 36)->comment('user ID');
            $table->string('user_agent')->comment('user agent');
            $table->string('ip', 15)->comment('ip');
            $table->timestamp('created_at')->nullable()->comment('created date');

            $table->index('user_id');
        });
    }

    /**
     * Run after installation.
     *
     * @return void
     */
    public function installed()
    {
        DB::table('config')->insert([
            ['name' => 'user', 'vars' => '[]'],
            ['name' => 'user.common', 'vars' => '{"secureLevel":"low","useCaptcha":false,"webmasterName":"webmaster","webmasterEmail":"webmaster@domain.com", "joinable":true}'],
            ['name' => 'toggleMenu@user', 'vars' => '{"activate":["user\/toggleMenu\/xpressengine@profile","user\/toggleMenu\/xpressengine@manage"]}']
        ]);
    }

    /**
     * Run after service activation.
     *
     * @return void
     */
    public function init()
    {
        // add default user groups
        $joinGroup = app('xe.user')->groups()->create(
            [
                'name' => '정회원',
                'description' => 'default user group'
            ]
        );
        app('xe.user')->groups()->create(
            [
                'name' => '준회원',
                'description' => 'sub user group'
            ]
        );
        $joinConfig = app('xe.config')->get('user.common');

        $joinConfig->set('joinGroup', $joinGroup->id);
        app('xe.config')->modify($joinConfig);

        // set admin's group
        auth()->user()->joinGroups($joinGroup);
    }

    /**
     * check updated
     *
     * @param null $installedVersion installed version
     *
     * @return bool
     */
    public function checkUpdated($installedVersion = null)
    {
        if ($this->checkNeedMergeConfig() == true) {
            return false;
        }

        return true;
    }

    /**
     * run update
     *
     * @param null $installedVersion installed version
     *
     * @return void
     */
    public function update($installedVersion = null)
    {
        if ($this->checkNeedMergeConfig() == true) {
            $this->mergeConfig();
        }
    }

    /**
     * check need user setting merge(common, join)
     *
     * @return bool
     */
    private function checkNeedMergeConfig()
    {
        return app('xe.config')->get('user.join') !== null ? true : false;
    }

    /**
     * run user setting merge(common, join)
     *
     * @return void
     */
    private function mergeConfig()
    {
        $commonConfig = app('xe.config')->get('user.common');
        $joinConfig = app('xe.config')->get('user.join');

        $commonConfigAttribute = $commonConfig->getPureAll();
        $joinConfigAttribute = $joinConfig->getPureAll();

        foreach ($joinConfigAttribute as $name => $value) {
            $commonConfigAttribute[$name] = $value;
        }

        app('xe.config')->put('user.common', $commonConfigAttribute);
        app('xe.config')->remove($joinConfig);
    }
}
