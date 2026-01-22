import React from 'react';
import { Link } from '@inertiajs/react';
import { HomeIcon, DocumentTextIcon, PencilSquareIcon, Cog6ToothIcon, ArrowPathIcon, PuzzlePieceIcon, ArrowRightOnRectangleIcon } from '@heroicons/react/24/outline';

export default function Index() {
    const menuItems = [
        { name: 'Dashboard', href: '/admin', icon: HomeIcon },
        { name: 'Pages', href: '/admin/pages', icon: DocumentTextIcon },
        { name: 'Posts', href: '/admin/posts', icon: PencilSquareIcon },
        { name: 'Updates', href: '/admin/updates', icon: ArrowPathIcon },
        { name: 'Plugins', href: '/admin/plugins', icon: PuzzlePieceIcon },
        { name: 'Users', href: '/admin/users', icon: PencilSquareIcon },
        { name: 'Settings', href: '/admin/settings', icon: Cog6ToothIcon },
    ];

    return (
        <div className="flex h-screen bg-gray-100">
            {/* Sidebar */}
            <div className="w-64 bg-white shadow-lg">
                <div className="p-4">
                    <h2 className="text-xl font-bold text-gray-800">DSC CMS Admin</h2>
                </div>
                <nav className="mt-4">
                    <ul>
                        {menuItems.map((item) => (
                            <li key={item.name}>
                                <Link
                                    href={item.href}
                                    className="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900"
                                >
                                    <item.icon className="w-5 h-5 mr-3" />
                                    {item.name}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>
                <div className="absolute bottom-0 w-64 p-4">
                    <Link
                        href="/admin/logout"
                        method="post"
                        className="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900"
                    >
                        <ArrowRightOnRectangleIcon className="w-5 h-5 mr-3" />
                        Logout
                    </Link>
                </div>
            </div>

            {/* Main content */}
            <div className="flex-1 p-8">
                <h1 className="text-3xl font-bold text-gray-800 mb-4">Updates</h1>
                <p className="text-gray-600 mb-8">Manage CMS updates and system maintenance</p>

                {/* Updates interface */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div className="bg-white p-6 rounded-lg shadow">
                        <h3 className="text-lg font-semibold text-gray-800 mb-2">System Status</h3>
                        <div className="space-y-2">
                            <div className="flex justify-between">
                                <span className="text-gray-600">Version:</span>
                                <span className="font-medium">1.3.0</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-600">Status:</span>
                                <span className="text-green-600 font-medium">Up to date</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-lg shadow">
                        <h3 className="text-lg font-semibold text-gray-800 mb-2">Database</h3>
                        <div className="space-y-2">
                            <button className="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                                Run Migrations
                            </button>
                            <button className="w-full bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">
                                Backup Database
                            </button>
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-lg shadow">
                        <h3 className="text-lg font-semibold text-gray-800 mb-2">Maintenance</h3>
                        <div className="space-y-2">
                            <button className="w-full bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 text-sm">
                                Enable Maintenance
                            </button>
                            <button className="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                                Check for Updates
                            </button>
                        </div>
                    </div>
                </div>

                {/* Update log */}
                <div className="mt-8 bg-white rounded-lg shadow">
                    <div className="p-6">
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Update History</h3>
                        <div className="space-y-4">
                            <div className="animate-pulse">
                                <div className="h-4 bg-gray-200 rounded w-full mb-2"></div>
                                <div className="h-3 bg-gray-200 rounded w-3/4"></div>
                            </div>
                            <div className="animate-pulse">
                                <div className="h-4 bg-gray-200 rounded w-full mb-2"></div>
                                <div className="h-3 bg-gray-200 rounded w-2/3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}