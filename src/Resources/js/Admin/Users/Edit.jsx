import React from 'react';

export default function Edit({ user, roles }) {
    return (
        <div className="flex h-screen bg-gray-100">
            <div className="w-64 bg-white shadow-lg"></div>
            <div className="flex-1 p-8">
                <h1 className="text-3xl font-bold text-gray-800 mb-4">Edit User</h1>
                <form method="POST" action={`/admin/users/${user.id}`}>
                    <input type="hidden" name="_method" value="PUT" />
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700">Name</label>
                        <input name="name" defaultValue={user.name} className="mt-1 block w-full border-gray-300 rounded-md" />
                    </div>
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700">Email</label>
                        <input name="email" defaultValue={user.email} className="mt-1 block w-full border-gray-300 rounded-md" />
                    </div>
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700">New Password (leave blank to keep)</label>
                        <input name="password" type="password" className="mt-1 block w-full border-gray-300 rounded-md" />
                    </div>
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input name="password_confirmation" type="password" className="mt-1 block w-full border-gray-300 rounded-md" />
                    </div>
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" defaultValue={(user.roles||[])[0]?.name || ''} className="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">-- none --</option>
                            {roles.map(r => <option key={r} value={r}>{r}</option>)}
                        </select>
                    </div>
                    <div className="flex items-center gap-4">
                        <button type="submit" className="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                        <form method="POST" action={`/admin/users/${user.id}/send-reset`}>
                            <button type="submit" className="bg-yellow-500 text-white px-3 py-2 rounded">Send Password Reset</button>
                        </form>
                    </div>
                </form>
            </div>
        </div>
    );
}
