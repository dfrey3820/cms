<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Buni CMS</title>
    <link rel="stylesheet" href="<?php echo asset('vendor/cms/tailwind.css'); ?>">
    <?php if (app()->bound(\Buni\Cms\Services\HookManager::class)) { app(\Buni\Cms\Services\HookManager::class)->doAction('cms_enqueue_scripts'); } ?>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Install Buni CMS
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Create your admin account to get started
                </p>
            </div>
            <form class="mt-8 space-y-6" action="/install" method="POST">
                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>" />
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="name" class="sr-only">Name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Admin Name"
                            value="<?php echo old('name'); ?>"
                        />
                        <?php if ($errors->has('name')): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo $errors->first('name'); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Email address"
                            value="<?php echo old('email'); ?>"
                        />
                        <?php if ($errors->has('email')): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo $errors->first('email'); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Password"
                        />
                        <?php if ($errors->has('password')): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo $errors->first('password'); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="password_confirmation" class="sr-only">Confirm Password</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Confirm Password"
                        />
                        <?php if ($errors->has('password_confirmation')): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo $errors->first('password_confirmation'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Install CMS
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>