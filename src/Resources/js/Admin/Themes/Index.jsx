import React from 'react';
import { Inertia } from '@inertiajs/inertia';
import Header from '@admin-core/components/Header';
import { Cog6ToothIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import menuItems from '@admin-core/menuItems';

export default function Index({ themes = [], active }) {
    // menuItems imported from admin-core

    function activate(themeId) {
        Inertia.post(`/admin/themes/${themeId}/activate`);
    }

    return (
        <div className="flex h-screen bg-gray-100">
            <div className="w-64 bg-white shadow-lg">
                <div className="p-4">
                    <h2 className="text-xl font-bold text-gray-800">DSC CMS Admin</h2>
                </div>
                <nav className="mt-4">
                    <ul>
                        {menuItems.map((item) => (
                            <li key={item.name}>
                                <a href={item.href} className="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900">
                                    <item.icon className="w-5 h-5 mr-3" />
                                    {item.name}
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>
                <div className="absolute bottom-0 w-64 p-4">
                    <a href="/admin/logout" className="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900">Logout</a>
                </div>
            </div>

            <div className="flex-1 flex flex-col">
                <Header />
                <div className="flex-1 p-8">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-800">Themes</h1>
                            <p className="text-gray-600">Manage installed themes</p>
                        </div>
                    </div>

                    <div className="grid grid-cols-3 gap-6">
                        {themes.map(t => (
                            <div key={t.id} className={`bg-white p-4 rounded-lg shadow ${active === t.id ? 'border-2 border-blue-500' : ''}`}>
                                <h3 className="text-lg font-semibold">{t.name}</h3>
                                <p className="text-sm text-gray-600">{t.description}</p>
                                <p className="text-xs text-gray-400">{t.author} • {t.version}</p>
                                <div className="mt-4 flex space-x-2">
                                    {active === t.id ? (
                                        <span className="px-3 py-1 bg-green-600 text-white rounded">Active</span>
                                    ) : (
                                        <button onClick={() => activate(t.id)} className="px-3 py-1 bg-blue-600 text-white rounded">Activate</button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
