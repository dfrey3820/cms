import React from 'react';
import { Link } from '@inertiajs/react';
import { HomeIcon, DocumentTextIcon, PencilSquareIcon, Cog6ToothIcon, ArrowPathIcon, PuzzlePieceIcon, ArrowRightOnRectangleIcon } from '@heroicons/react/24/outline';
import Header from '@admin-core/components/Header';
import menuItems from '@admin-core/menuItems';

export default function Index({ pages, auth }) {
    // menuItems imported from admin-core

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
            <div className="flex-1 flex flex-col">
                <Header auth={auth} />

                <div className="flex-1 p-8">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-800">Pages</h1>
                            <p className="text-gray-600">Manage your website pages</p>
                        </div>
                        <Link
                            href="/admin/pages/create"
                            className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
                        >
                            Create Page
                        </Link>
                    </div>

                {/* Pages list */}
                <div className="bg-white rounded-lg shadow">
                    <div className="p-6">
                        {pages && pages.length > 0 ? (
                            <ul className="space-y-4">
                                {pages.map(page => (
                                    <li key={page.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                        <div>
                                            <h3 className="text-lg font-semibold text-gray-800">{page.title}</h3>
                                            <p className="text-gray-600">{page.slug}</p>
                                        </div>
                                        <Link
                                            href={`/admin/pages/${page.id}/edit`}
                                            className="text-blue-600 hover:text-blue-800"
                                        >
                                            Edit
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-gray-600">No pages found. Create your first page</p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}