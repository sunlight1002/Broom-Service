import React, { useState, useEffect } from "react";
import Sidebar from "../../../Layouts/Sidebar";
import ReactPaginate from "react-paginate";
import { Table, Thead, Tbody, Tr, Th, Td } from 'react-super-responsive-table'
import { Link } from "react-router-dom";
import axios from "axios";
import Moment from 'moment';
import { Base64 } from "js-base64";

export default function Invoices() {

    const [loading, setLoading] = useState("Loading...");
    const [invoices, setInvoices] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const [payId,setPayID] = useState(0);
    const [paidAmount,setPaidAmount] = useState('');
    const [amount,setAmount]         = useState();
    const [txn,setTxn]               = useState('');

    const getInvoices = () => {
        axios
            .get('/api/admin/invoices', { headers })
            .then((res) => {
                if (res.data.invoices.data.length > 0) {
                    setInvoices(res.data.invoices.data);
                    setPageCount(res.data.invoices.last_page);
                } else {
                    setInvoices([]);
                    setLoading('No Invoice found');
                }
            })
    }

    const copy = [...invoices];
    const [order, setOrder] = useState('ASC');
    const sortTable = (e, col) => {

        let n = e.target.nodeName;
        if (n != "SELECT") {
            if (n == "TH") {
                let q = e.target.querySelector('span');
                if (q.innerHTML === "↑") {
                    q.innerHTML = "↓";
                } else {
                    q.innerHTML = "↑";
                }

            } else {
                let q = e.target;
                if (q.innerHTML === "↑") {
                    q.innerHTML = "↓";
                } else {
                    q.innerHTML = "↑";
                }
            }
        }

        if (order == 'ASC') {
            const sortData = [...copy].sort((a, b) => (a[col] < b[col] ? 1 : -1));
            setInvoices(sortData);
            setOrder('DESC');
        }
        if (order == 'DESC') {
            const sortData = [...copy].sort((a, b) => (a[col] < b[col] ? -1 : 1));
            setInvoices(sortData);
            setOrder('ASC');
        }

    }

    const handlePageClick = async (data) => {
        let currentPage = data.selected + 1;
        axios
            .get("/api/admin/invoices?page=" + currentPage, { headers })
            .then((response) => {
                if (response.data.invoices.data.length > 0) {
                    setInvoices(response.data.invoices.data);
                    setPageCount(response.data.invoices.last_page);
                } else {
                    setLoading("No Invoice Found");
                }
            });
    };

    const handlePayment = () => {
        if (paidAmount == '') { window.alert('Please enter amount'); return; }

        const m = document.querySelector('.mode').value;
        const stat = ((paidAmount) >= (amount)) ? 'Paid' : 'Partially Paid';
        const pm = {
                'cc' : 'Credit Card',
                'mt' : 'Bank Transfer',
                'cash' : 'Cash',
                'cheque': 'Cheque'
            }
        const data = {
            'paid_amount': paidAmount,
            'pay_method': paidAmount > 0 ? pm[m] : '',
            'txn_id': txn,
            'status': paidAmount > 0 ? stat : 'Unpaid',
        }
       
      
        axios.post(`/api/admin/update-invoice/${payId}`, { data }, { headers })
            .then((res) => {
                document.querySelector('.closeb1').click();
                getInvoices('');
                setPaidAmount('');
                setPayID(0);
             
            })

    }


    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Invoice!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .get(`/api/admin/delete-invoice/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Invoice has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getInvoices();
                        }, 1000);
                    });
            }
        });
    };

    useEffect(() => {
        getInvoices();
    }, []);
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">Manage Invoices</h1>
                        </div>
                        {/*<div className="col-sm-6">
                            <Link
                                to="/admin/add-invoice"
                                className="ml-2 btn btn-pink addButton">
                                <i className="btn-icon fas fa-plus-circle"></i>
                                Create Invoice
                            </Link>
                        </div>*/}
                    </div>
                </div>
                <div className="sales-filter">
                    <div className="row">
                        <div className="col-sm-3 col-6">
                            <div className="form-group">
                                <label className="control-label">From Date</label>
                                <input type="date" className="form-control" />
                            </div>
                        </div>
                        <div className="col-sm-3 col-6">
                            <div className="form-group">
                                <label className="control-label">To Date</label>
                                <input type="date" className="form-control" />
                            </div>
                        </div>
                        <div className="col-sm-3 col-6">
                            <div className="form-group">
                                <label className="control-label">Invoice ID</label>
                                <input type="text" className="form-control" placeholder="Order ID" />
                            </div>
                        </div>
                        <div className="col-sm-3 col-6">
                            <div className="form-group">
                                <label className="control-label">Customer</label>
                                <input type="text" className="form-control" placeholder="Customer" />
                            </div>
                        </div>
                        <div className="col-sm-3 col-6">
                            <div className="form-group">
                                <label className="control-label">Transaction ID/Ref.</label>
                                <input type="text" className="form-control" placeholder="Customer" />
                            </div>
                        </div>
                        <div className="col-sm-3 col-6">
                            <div className="form-group">
                                <label className="control-label">Payment mode</label>
                                <select className="form-control">
                                    <option>Please Select</option>
                                    <option>Credit Card</option>
                                    <option>Bank Transfer</option>
                                    <option>By Cheque</option>
                                    <option>By Cash</option>
                                </select>
                            </div>
                        </div>
                        <div className="col-sm-2 col-6">
                            <div className="form-group">
                                <label className="control-label">Status</label>
                                <select className="form-control">
                                    <option>Please Select</option>
                                    <option>Paid</option>
                                    <option>Unpaid</option>
                                    <option>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div className="col-sm-2 col-6">
                            <label className="control-label d-block">&nbsp;</label>
                            <button className="btn btn-pink" style={{minWidth: "100px"}}>Filter</button>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <div className="table-responsive">
                                {invoices.length > 0 ? (
                                    <Table className="table table-bordered">
                                        <Thead>
                                            <Tr>
                                                <Th scope="col" style={{ cursor: "pointer" }} onClick={(e) => { sortTable(e, 'id') }}  >    #Invoice ID     <span className="arr"> &darr;</span></Th>
                                                <Th scope="col" style={{ cursor: "pointer" }} onClick={(e) => { sortTable(e, 'amount') }}  >Total Amount       <span className="arr"> &darr;</span></Th>
                                                <Th scope="col" style={{ cursor: "pointer" }} onClick={(e) => { sortTable(e, 'amount') }}  >Paid Amount       <span className="arr"> &darr;</span></Th>
                                                <Th scope="col" style={{ cursor: "pointer" }} onClick={(e) => { sortTable(e, 'created_at') }}  >Created Date      <span className="arr"> &darr;</span></Th>
                                                <Th scope="col" style={{ cursor: "pointer" }} onClick={(e) => { sortTable(e, 'due_date') }} >Due Date          <span className="arr"> &darr;</span></Th>
                                                <Th scope="col"  >Customer   </Th>
                                                <Th scope="col" style={{ cursor: "pointer" }} onClick={(e) => { sortTable(e, 'status') }}  >Status            <span className="arr"> &darr;</span></Th>
                                                <Th scope="col" style={{ cursor: "pointer" }} onClick={(e) => { sortTable(e, 'status') }}  >Doc Status        <span className="arr"> &darr;</span></Th>
                                                <Th scope="col"  >Transaction ID/Ref.</Th>
                                                <Th scope="col"  >Payment Mode</Th>
                                                <Th scope="col">Action</Th>
                                            </Tr>
                                        </Thead>
                                        <Tbody>
                                            {invoices &&
                                                invoices.map((item, index) => {
                                                    let services = (item.services != undefined && item.services != null) ? JSON.parse(item.services) : []

                                                    return (
                                                        <Tr>
                                                            <Td>#{item.invoice_id}</Td>
                                                            <Td>{item.amount} ILS</Td>
                                                            <Td>{item.paid_amount} ILS</Td>
                                                            <Td>{Moment(item.created_at).format('DD, MMM Y')}</Td>
                                                            <Td>{(item.due_date != null) ? Moment(item.due_date).format('DD, MMM Y') : 'NA'}</Td>
                                                            <Td><Link to={`/admin/view-client/${item.client.id}`}>{item.client.firstname + " " + item.client.lastname}</Link></Td>
                                                            <Td>
                                                                {item.status}
                                                            </Td>
                                                            <Td>
                                                                {item.invoice_icount_status}
                                                            </Td>
                                                            <Td>
                                                                {item.txn_id ? item.txn_id : 'NA'}
                                                            </Td>
                                                            <Td>
                                                                {item.pay_method ? item.pay_method : 'Credit Card'}
                                                            </Td>
                                                            <Td>
                                                                <div className="action-dropdown dropdown">
                                                                    <button type="button" className="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                                                        <i className="fa fa-ellipsis-vertical"></i>
                                                                    </button>

                                                                    <div className="dropdown-menu">
                                                                        <a target="_blank" href={item.doc_url} className="dropdown-item">View Invoice</a>
                                                                        {
                                                                            item.status != 'Paid' && <button onClick={(e) => {setPayID(item.id);setAmount(item.amount)}} data-toggle="modal" data-target="#exampleModal" className="dropdown-item"
                                                                            >Add Payment</button>
                                                                        }
                                                                       
                                                                        {
                                                                            item.invoice_icount_status == 'Open' && <button onClick={(e) => {setPayID(item.id);setAmount(item.amount)}} data-toggle="modal" data-target="#exampleModal" className="dropdown-item"
                                                                            >Close Doc</button>
                                                                        }
                                                                        <button onClick={e => handleDelete(item.id)} className="dropdown-item"
                                                                        >Delete</button>
                                                                    </div>
                                                                </div>
                                                            </Td>
                                                        </Tr>
                                                    )
                                                })}
                                        </Tbody>
                                    </Table>)
                                    : (
                                        <div className="form-control text-center"> No Invoice Found</div>
                                    )}

                                {invoices.length > 0 ? (
                                    <ReactPaginate
                                        previousLabel={"Previous"}
                                        nextLabel={"Next"}
                                        breakLabel={"..."}
                                        pageCount={pageCount}
                                        marginPagesDisplayed={2}
                                        pageRangeDisplayed={3}
                                        onPageChange={handlePageClick}
                                        containerClassName={
                                            "pagination justify-content-end mt-3"
                                        }
                                        pageClassName={"page-item"}
                                        pageLinkClassName={"page-link"}
                                        previousClassName={"page-item"}
                                        previousLinkClassName={"page-link"}
                                        nextClassName={"page-item"}
                                        nextLinkClassName={"page-link"}
                                        breakClassName={"page-item"}
                                        breakLinkClassName={"page-link"}
                                        activeClassName={"active"}
                                    />
                                ) : ''}



                            </div>
                        </div>
                    </div>
                </div>

                <div className="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModal" aria-hidden="true">
                <div className="modal-dialog" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModal">Add Payment</h5>
                            <button type="button" className="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div className="modal-body">

                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Amount
                                        </label>
                                        <input
                                            type="text"
                                             value={paidAmount}
                                                onChange={(e) =>
                                                    setPaidAmount(e.target.value)
                                                }
                                            className="form-control"
                                            required
                                            placeholder="Enter Amount"
                                        ></input>

                                    </div>
                                </div>
                                    
                            </div>

                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Transaction / Refrence ID
                                        </label>
                                        <input
                                            type="text"
                                             value={txn}
                                            onChange={(e) =>
                                                setTxn(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder="Enter Transaction / Refrence ID"
                                        ></input>

                                    </div>
                                </div>
                                    
                            </div>

                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                        Payment Mode
                                        </label>
                                        <select   name='mode' className='form-control mode'>
                                         <option  value='mt'    >Bank Transfer</option>
                                         <option  value='cash' >By Cash</option>
                                         <option  value='cc'     >Credit Card</option>
                                         <option  value='cheque' >By Cheque</option>
                                        </select>

                                    </div>
                                </div>
                                    
                            </div>
                            
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-secondary closeb1" data-dismiss="modal">Close</button>
                            <button type="button" onClick={handlePayment} className="btn btn-primary">Save Payment</button>
                        </div>
                    </div>
                </div>
            </div>

            </div>
        </div>

    )
}