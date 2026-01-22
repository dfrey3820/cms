import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { HomeIcon, DocumentTextIcon, PencilSquareIcon, Cog6ToothIcon, ArrowPathIcon, PuzzlePieceIcon, ArrowRightOnRectangleIcon } from '@heroicons/react/24/outline';
import Header from '../components/Header';

export default function Index({ user, auth, twoFactorEnabled }) {
    const menuItems = [
        { name: 'Dashboard', href: '/admin', icon: HomeIcon },
        { name: 'Pages', href: '/admin/pages', icon: DocumentTextIcon },
        { name: 'Posts', href: '/admin/posts', icon: PencilSquareIcon },
        { name: 'Updates', href: '/admin/updates', icon: ArrowPathIcon },
        { name: 'Plugins', href: '/admin/plugins', icon: PuzzlePieceIcon },
        { name: 'Users', href: '/admin/users', icon: PencilSquareIcon },
        { name: 'Settings', href: '/admin/settings', icon: Cog6ToothIcon },
    ];

    const { data: profileData, setData: setProfileData, post: postProfile, processing: processingProfile, errors: profileErrors } = useForm({
        name: user.name,
    });

    const { data: passwordData, setData: setPasswordData, post: postPassword, processing: processingPassword, errors: passwordErrors, reset: resetPassword } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleProfileSubmit = (e) => {
        e.preventDefault();
        postProfile(route('cms.admin.profile.update'), {
            onSuccess: () => {
                alert('Profile updated successfully!');
            },
        });
    };

    const handlePasswordSubmit = (e) => {
        e.preventDefault();
        postPassword(route('cms.admin.profile.password'), {
            onSuccess: () => {
                resetPassword();
                alert('Password updated successfully!');
            },
        });
    };

    return (
        <>
            <Head title="Profile" />

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
                        <h1 className="text-3xl font-bold text-gray-800 mb-4">Profile</h1>
                        <p className="text-gray-600 mb-8">Manage your account settings</p>

                        <div className="space-y-6">
                            {/* Profile Information */}
                            <div className="bg-white p-6 rounded-lg shadow">
                                <h2 className="text-xl font-semibold text-gray-800 mb-4">Profile Information</h2>
                                <form onSubmit={handleProfileSubmit}>
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700">Name</label>
                                        <input
                                            type="text"
                                            value={profileData.name}
                                            onChange={(e) => setProfileData('name', e.target.value)}
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        {profileErrors.name && <p className="mt-1 text-sm text-red-600">{profileErrors.name}</p>}
                                    </div>
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700">Email</label>
                                        <input
                                            type="email"
                                            value={user.email}
                                            disabled
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100"
                                        />
                                        <p className="mt-1 text-sm text-gray-500">Email cannot be changed</p>
                                    </div>
                                    <button
                                        type="submit"
                                        disabled={processingProfile}
                                        className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        Update Profile
                                    </button>
                                </form>
                            </div>

                            {/* Password */}
                            <div className="bg-white p-6 rounded-lg shadow">
                                <h2 className="text-xl font-semibold text-gray-800 mb-4">Change Password</h2>
                                <form onSubmit={handlePasswordSubmit}>
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700">Current Password</label>
                                        <input
                                            type="password"
                                            value={passwordData.current_password}
                                            onChange={(e) => setPasswordData('current_password', e.target.value)}
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        {passwordErrors.current_password && <p className="mt-1 text-sm text-red-600">{passwordErrors.current_password}</p>}
                                    </div>
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700">New Password</label>
                                        <input
                                            type="password"
                                            value={passwordData.password}
                                            onChange={(e) => setPasswordData('password', e.target.value)}
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        {passwordErrors.password && <p className="mt-1 text-sm text-red-600">{passwordErrors.password}</p>}
                                    </div>
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                        <input
                                            type="password"
                                            value={passwordData.password_confirmation}
                                            onChange={(e) => setPasswordData('password_confirmation', e.target.value)}
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        {passwordErrors.password_confirmation && <p className="mt-1 text-sm text-red-600">{passwordErrors.password_confirmation}</p>}
                                    </div>
                                    <button
                                        type="submit"
                                        disabled={processingPassword}
                                        className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        Update Password
                                    </button>
                                </form>
                            </div>

                            {/* 2FA Setup */}
                            {twoFactorEnabled && (
                                <div className="bg-white p-6 rounded-lg shadow">
                                    <h2 className="text-xl font-semibold text-gray-800 mb-4">Two-Factor Authentication</h2>
                                    <p className="text-gray-600 mb-4">Two-factor authentication is enabled. You can set it up here.</p>
                                    {/* Add 2FA setup UI here */}
                                    <button className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                        Setup 2FA
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}