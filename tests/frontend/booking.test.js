import { fireEvent, waitFor } from '@testing-library/dom';
import '@testing-library/jest-dom';
import { renderBookingForm } from '../../frontend/js/booking';

describe('Booking Form Tests', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div id="bookingForm">
                <select id="serviceSelect"></select>
                <input type="date" id="bookingDate">
                <select id="timeSlots"></select>
                <button type="submit">Book Now</button>
            </div>
        `;
    });

    test('should load services on page load', async () => {
        const mockServices = [
            { id: 1, name: 'Basic Wash', price: 10 },
            { id: 2, name: 'Premium Wash', price: 20 }
        ];

        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ success: true, services: mockServices })
            })
        );

        renderBookingForm();

        await waitFor(() => {
            const options = document.querySelectorAll('#serviceSelect option');
            expect(options.length).toBe(mockServices.length + 1); // +1 for default option
        });
    });

    test('should show error for invalid date selection', async () => {
        renderBookingForm();

        const dateInput = document.getElementById('bookingDate');
        const pastDate = new Date();
        pastDate.setDate(pastDate.getDate() - 1);
        
        fireEvent.change(dateInput, { 
            target: { value: pastDate.toISOString().split('T')[0] } 
        });

        const errorMessage = document.querySelector('.error-message');
        expect(errorMessage).toHaveTextContent('Please select a future date');
    });

    test('should handle successful booking submission', async () => {
        const mockBookingResponse = {
            success: true,
            booking_id: 123,
            message: 'Booking created successfully'
        };

        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve(mockBookingResponse)
            })
        );

        renderBookingForm();

        // Fill form
        fireEvent.change(document.getElementById('serviceSelect'), {
            target: { value: '1' }
        });

        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        fireEvent.change(document.getElementById('bookingDate'), {
            target: { value: tomorrow.toISOString().split('T')[0] }
        });

        fireEvent.change(document.getElementById('timeSlots'), {
            target: { value: '10:00:00' }
        });

        // Submit form
        fireEvent.click(document.querySelector('button[type="submit"]'));

        await waitFor(() => {
            const successMessage = document.querySelector('.success-message');
            expect(successMessage).toHaveTextContent('Booking created successfully');
        });
    });
});