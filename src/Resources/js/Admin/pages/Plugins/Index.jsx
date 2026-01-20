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
                <h1 className="text-3xl font-bold text-gray-800 mb-4">Plugins</h1>
                <p className="text-gray-600 mb-8">Manage your CMS plugins here.</p>

                {/* Plugins interface placeholder */}
                <div className="bg-white p-6 rounded-lg shadow">
                    <p>Plugins management interface will be implemented here.</p>
                </div>
            </div>
        </div>
    );
}