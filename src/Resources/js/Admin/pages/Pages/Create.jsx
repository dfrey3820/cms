import React from 'react';
import { useForm } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, errors } = useForm({
        title: '',
        slug: '',
        content: '',
        blocks: [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/pages');
    };

    return (
        <form onSubmit={handleSubmit}>
            <input
                type="text"
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                placeholder="Title"
            />
            {errors.title && <div>{errors.title}</div>}

            <input
                type="text"
                value={data.slug}
                onChange={e => setData('slug', e.target.value)}
                placeholder="Slug"
            />
            {errors.slug && <div>{errors.slug}</div>}

            <textarea
                value={data.content}
                onChange={e => setData('content', e.target.value)}
                placeholder="Content"
            />

            <button type="submit">Create Page</button>
        </form>
    );
}