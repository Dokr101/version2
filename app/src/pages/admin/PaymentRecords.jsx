import { useState, useEffect } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { DataTable } from '../../components/ui/DataTable';
import { StatCard } from '../../components/ui/StatCard';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { CreditCard, DollarSign, BarChart } from 'lucide-react';

export const PaymentRecords = () => {
  const [payments, setPayments] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchPayments = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/version2/api/payments/list.php');
      setPayments(response.data.payments || []);
      setStats(response.data.stats || null);
    } catch (error) {
      console.error('Failed to fetch payments list:', error);
      toast.error('Could not load transaction ledgers.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPayments();
  }, []);

  const columns = [
    {
      header: 'Payment ID',
      accessor: 'payment_id',
      cell: (row) => <strong>#{row.payment_id}</strong>
    },
    {
      header: 'Booking ID',
      accessor: 'booking_id',
      cell: (row) => <span>#{row.booking_id} ({row.room_type})</span>
    },
    {
      header: 'Guest Name',
      accessor: 'guest_name',
      cell: (row) => <span style={{ color: 'var(--text-primary)', fontWeight: 600 }}>{row.guest_name}</span>
    },
    {
      header: 'Transaction ID',
      accessor: 'transaction_id',
      cell: (row) => (
        <span 
          style={{ fontFamily: 'monospace', fontSize: '0.75rem', color: 'var(--text-muted)' }}
          title={row.transaction_id}
        >
          {row.transaction_id ? (row.transaction_id.length > 15 ? `${row.transaction_id.slice(0, 15)}...` : row.transaction_id) : 'Cash'}
        </span>
      )
    },
    {
      header: 'Method',
      accessor: 'payment_method',
      cell: (row) => <span>{row.payment_method}</span>
    },
    {
      header: 'Paid Date',
      accessor: 'created_at',
      cell: (row) => new Date(row.created_at).toLocaleDateString('en-US', { dateStyle: 'medium' })
    },
    {
      header: 'Amount Paid',
      accessor: 'amount',
      cell: (row) => <strong style={{ color: 'var(--success)' }}>Rs. {row.amount.toLocaleString('en-IN')}</strong>
    },
    {
      header: 'Status',
      accessor: 'status',
      cell: (row) => <StatusBadge status={row.status === 'completed' ? 'paid' : 'pending'} />
    }
  ];

  return (
    <div className="page animate-fade-in">
      <div className="stats-row stagger-children">
        <StatCard 
          title="Total Revenue Realized" 
          value={`Rs. ${stats?.totalRevenue?.toLocaleString('en-IN') || '0'}`} 
          icon={DollarSign} 
          color="success" 
        />
        <StatCard 
          title="Transactions Completed" 
          value={stats?.totalTransactions || 0} 
          icon={CreditCard} 
          color="primary" 
        />
        <StatCard 
          title="Average Ticket Size" 
          value={`Rs. ${Math.round(stats?.averageTransaction || 0).toLocaleString('en-IN')}`} 
          icon={BarChart} 
          color="info" 
        />
      </div>

      <DataTable 
        columns={columns}
        data={payments}
        loading={loading}
        searchKey="guest_name"
        searchPlaceholder="Search guest name..."
        emptyMessage="No completed payment transactions registered."
      />
    </div>
  );
};
export default PaymentRecords;
