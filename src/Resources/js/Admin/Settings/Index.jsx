import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { HomeIcon, DocumentTextIcon, PencilSquareIcon, Cog6ToothIcon, ArrowPathIcon, PuzzlePieceIcon, ArrowRightOnRectangleIcon } from '@heroicons/react/24/outline';
import Header from '../components/Header';

export default function Index({ settings, auth }) {
    const [activeTab, setActiveTab] = useState('site');
    const { data, setData, post, processing, errors, reset } = useForm({});

    // Initialize form data with existing settings
    React.useEffect(() => {
        if (settings) {
            const initialData = {};
            Object.keys(settings).forEach(group => {
                if (settings[group]) {
                    settings[group].forEach(setting => {
                        initialData[setting.key] = setting.value || '';
                    });
                }
            });
            reset(initialData);
        }
    }, [settings, reset]);

    const menuItems = [
        { name: 'Dashboard', href: '/admin', icon: HomeIcon },
        { name: 'Pages', href: '/admin/pages', icon: DocumentTextIcon },
        { name: 'Posts', href: '/admin/posts', icon: PencilSquareIcon },
        { name: 'Updates', href: '/admin/updates', icon: ArrowPathIcon },
        { name: 'Plugins', href: '/admin/plugins', icon: PuzzlePieceIcon },
        { name: 'Settings', href: '/admin/settings', icon: Cog6ToothIcon },
    ];

    const tabs = [
        { id: 'site', label: 'Site', icon: 'fas fa-globe' },
        { id: 'system', label: 'System', icon: 'fas fa-cogs' },
        { id: 'server', label: 'Server', icon: 'fas fa-server' },
        { id: 'mail', label: 'Mail', icon: 'fas fa-envelope' },
        { id: 'database', label: 'Database', icon: 'fas fa-database' },
    ];

    const handleInputChange = (key, value) => {
        setData(key, value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('cms.admin.settings.update'), {
            onSuccess: () => {
                // Show success message or redirect
                alert('Settings updated successfully!');
            },
            onError: (errors) => {
                console.error('Settings update failed:', errors);
            }
        });
    };

    const renderTabContent = () => {
        const groupSettings = settings[activeTab] || [];

        return (
            <div className="space-y-6">
                {groupSettings.map(setting => (
                    <div key={setting.key} className="bg-white p-6 rounded-lg shadow-sm border">
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            {setting.key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                        </label>
                        <input
                            type="text"
                            value={data[setting.key] || setting.value || ''}
                            onChange={(e) => handleInputChange(setting.key, e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder={`Enter ${setting.key}`}
                        />
                        {errors[setting.key] && <p className="mt-1 text-sm text-red-600">{errors[setting.key]}</p>}
                    </div>
                ))}
            </div>
        );
    };

    return (
        <>
            <Head title="Settings" />

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
                        <div className="mb-6">
                            <h1 className="text-3xl font-bold text-gray-800">Settings</h1>
                            <p className="text-gray-600">Configure your CMS settings here.</p>
                        </div>

                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div className="p-6">
                            <h2 className="text-2xl font-bold text-gray-900 mb-6">Global Configuration</h2>

                            {/* Tabs */}
                            <div className="border-b border-gray-200 mb-6">
                                <nav className="-mb-px flex space-x-8">
                                    {tabs.map(tab => (
                                        <button
                                            key={tab.id}
                                            onClick={() => setActiveTab(tab.id)}
                                            className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                                activeTab === tab.id
                                                    ? 'border-blue-500 text-blue-600'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }`}
                                        >
                                            <i className={`${tab.icon} mr-2`}></i>
                                            {tab.label}
                                        </button>
                                    ))}
                                </nav>
                            </div>

                            {/* Tab Content */}
                            <form onSubmit={handleSubmit}>
                                {renderTabContent()}

                                <div className="mt-6 flex justify-end">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                        style={{backgroundColor: '#009cde'}}
                                        onMouseOver={(e) => e.target.style.backgroundColor = '#007bb8'}
                                        onMouseOut={(e) => e.target.style.backgroundColor = '#009cde'}
                                    >
                                        {processing ? 'Saving...' : 'Save Settings'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</>
    );
}