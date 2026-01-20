import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';

export default function Index({ settings }) {
    const [activeTab, setActiveTab] = useState('site');
    const { data, setData, post, processing, errors } = useForm({});

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
        post(route('cms.admin.settings.update'));
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
                            style={{'--tw-ring-color': '#009cde'}}
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

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div className="p-6">
                            <h1 className="text-2xl font-bold text-gray-900 mb-6">Global Configuration</h1>

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
                                            style={activeTab === tab.id ? {'border-color': '#009cde', 'color': '#009cde'} : {}}
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
        </>
    );
}