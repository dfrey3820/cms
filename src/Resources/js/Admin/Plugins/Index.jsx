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
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-800">Plugins</h1>
                        <p className="text-gray-600">Manage your CMS plugins</p>
                    </div>
                    <Link
                        href="/admin/plugins/create"
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
                    >
                        Add Plugin
                    </Link>
                </div>

                {/* Plugins list */}
                <div className="bg-white rounded-lg shadow">
                    <div className="p-6">
                        <div className="space-y-4">
                            {/* Skeleton loading items */}
                            {Array.from({ length: 3 }).map((_, index) => (
                                <div key={index} className="animate-pulse flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div className="flex items-center space-x-4">
                                        <div className="w-12 h-12 bg-gray-200 rounded-lg"></div>
                                        <div>
                                            <div className="h-4 bg-gray-200 rounded w-48 mb-2"></div>
                                            <div className="h-3 bg-gray-200 rounded w-64"></div>
                                        </div>
                                    </div>
                                    <div className="flex space-x-2">
                                        <div className="h-8 bg-gray-200 rounded w-16"></div>
                                        <div className="h-8 bg-gray-200 rounded w-20"></div>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <div className="mt-4 text-center text-gray-500">
                            <p>No plugins installed. <Link href="/admin/plugins/create" className="text-blue-600 hover:text-blue-800">Add your first plugin</Link></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}