import React from 'react';
import { Link } from '@inertiajs/react';
import { HomeIcon, DocumentTextIcon, PencilSquareIcon, Cog6ToothIcon, ArrowPathIcon, PuzzlePieceIcon, ArrowRightOnRectangleIcon } from '@heroicons/react/24/outline';
import Header from '../components/Header';

export default function Index({ auth }) {
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
            <div className="flex-1 flex flex-col">
                <Header auth={auth} />

                <div className="flex-1 p-8">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-800">Posts</h1>
                        <p className="text-gray-600">Manage your blog posts</p>
                    </div>
                    <Link
                        href="/admin/posts/create"
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
                    >
                        Create Post
                    </Link>
                </div>

                {/* Posts list */}
                <div className="bg-white rounded-lg shadow">
                    <div className="p-6">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Title
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Author
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {/* Skeleton loading rows */}
                                    {Array.from({ length: 5 }).map((_, index) => (
                                        <tr key={index} className="animate-pulse">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="h-4 bg-gray-200 rounded w-48"></div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="h-4 bg-gray-200 rounded w-16"></div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="h-4 bg-gray-200 rounded w-24"></div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="h-4 bg-gray-200 rounded w-20"></div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right">
                                                <div className="h-4 bg-gray-200 rounded w-12 ml-auto"></div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="mt-4 text-center text-gray-500">
                            <p>No posts found. Create your first post</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}