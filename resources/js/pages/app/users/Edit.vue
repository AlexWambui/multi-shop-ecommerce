<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import users from '@/routes/users';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FormError from '@/components/custom/FormError.vue';

interface Props {
    user: {
        data: {
            id: number;
            name: string;
            email: string;
            role: number;
            status: number;
            is_active: boolean;
        };
    };
}

const props = defineProps<Props>();

// Extract the actual user data from the nested structure
const userData = props.user.data;

const ROLES = {
    SUPER_ADMIN: 0,
    ADMIN: 1
} as const;

const STATUS = {
    INACTIVE: 0,
    ACTIVE: 1,
    SUSPENDED: 2,
} as const;

const roleOptions = [
    { value: ROLES.SUPER_ADMIN, label: 'Super Admin' },
    { value: ROLES.ADMIN, label: 'Admin' }
];

const statusOptions = [
    { value: STATUS.INACTIVE, label: 'Inactive' },
    { value: STATUS.ACTIVE, label: 'Active' },
    { value: STATUS.SUSPENDED, label: 'Suspended' },
];

const form = useForm({
    name: userData.name,
    email: userData.email,
    password: '',
    password_confirmation: '',
    role: userData.role,
    status: userData.status,
});

const handleSubmit = () => {
    form.put(users.update(userData.id).url);
};
</script>

<template>
    <Head title="Edit User" />

    <div class="app-container">
        <div class="form edit-user">
            <div class="form-header">
                <Link :href="users.index().url">&larr;</Link>
                <h2>Edit User</h2>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="section-title">Basic Information</div>
                
                <div class="inputs-group-wrapper">
                    <div class="inputs-group">
                        <Label for="name" class="required">Name</Label>
                        <Input v-model="form.name" type="text" placeholder="Full name" autofocus />
                        <FormError :error="form.errors.name" />
                    </div>

                    <div class="inputs-group">
                        <Label for="email" class="required">Email Address</Label>
                        <Input v-model="form.email" type="email" placeholder="Email address" />
                        <FormError :error="form.errors.email" />
                    </div>
                </div>

                <div class="inputs-group-wrapper">
                    <div class="inputs-group">
                        <Label for="role" class="required">User Role</Label>
                        <Select v-model="form.role">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select user role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem 
                                        v-for="option in roleOptions" 
                                        :key="option.value" 
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FormError :error="form.errors.role" />
                    </div>

                    <div class="inputs-group">
                        <Label for="status" class="required">Account Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select account status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem 
                                        v-for="option in statusOptions" 
                                        :key="option.value" 
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FormError :error="form.errors.status" />
                    </div>
                </div>

                <div class="inputs-group-wrapper">
                    <div class="inputs-group">
                        <Label for="password" class="required">Password</Label>
                        <Input v-model="form.password" type="password" placeholder="Password" />
                        <FormError :error="form.errors.password" />
                    </div>

                    <div class="inputs-group">
                        <Label for="password_confirmation" class="required">Confirm Password</Label>
                        <Input v-model="form.password_confirmation" type="password" placeholder="Confirm password" />
                        <FormError :error="form.errors.password_confirmation" />
                    </div>
                </div>

                <div class="submit-buttons">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        {{ form.processing ? 'Updating User...' : 'Update User' }}
                    </Button>

                    <Link :href="users.index().url">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>