import React, { useState, useEffect } from 'react';
import Sidebar from "../../../Layouts/Sidebar";
import { Link } from "react-router-dom";
import axios from 'axios';
import Moment from 'moment';
import { useAlert } from 'react-alert';
import { useNavigate } from "react-router-dom";
import Select from 'react-select';


export default function AddInvoce() {

    const [amount, setAmount] = useState();
    const [dueDate, setDueDate] = useState();
    const [customer, setCustomer] = useState();
    const [job, setJob] = useState();
    const [subtotal, setSubtotal] = useState();
    const [taxper, setTaxPer] = useState();
    const [taxAmount, setTaxAmount] = useState();
    const [services, setServices] = useState();
    const [selectedJobs, setSelectedJobs] = useState(null);
  
    const [lng, setLng] = useState();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const [clients, setClients] = useState();
    const [cjobs, setCjobs] = useState();
    const [jservices, setjServices] = useState();

    const [corders, setCOrders] = useState();

    const alert = useAlert();
    const navigate = useNavigate();

    const getCustomers = () => {
        axios
            .get(`/api/admin/all-clients`, { headers })
            .then((res) => {
                setClients(res.data.clients);
            });
    }

    const clientJobs = (cid) => {
        setSelectedJobs(null);
        setCjobs([]);
        axios
            .post(`/api/admin/get-client-jobs`, { cid }, { headers })
            .then((res) => {
                let jar = [];
                const j = res.data.jobs;

                if (j.length > 0) {
                    for (let i in j) {
                        let n = Moment(j[i].start_date).format('DD - MMM') + " | " + j[i].shifts;
                        jar.push(
                            { value: j[i].id, label: n },
                        );
                    }
                    setCjobs(jar);
                }

            })
    }

    const clientOrders = (cid) => {
        setSelectedJobs(null);
        setCOrders([]);
        axios
            .post(`/api/admin/get-client-invorders`, { cid }, { headers })
            .then((res) => {
                let jar = [];
                const j = res.data.orders;

                if (j.length > 0) {
                    for (let i in j) {
                        let n = Moment(j[i].start_date).format('DD - MMM') + " | " + j[i].shifts;
                        jar.push(
                            { value: j[i].id, label: n },
                        );
                    }
                    setCjobs(jar);
                }

            })
    }

    function onlyUnique(value, index, array) {
        return array.indexOf(value) === index;
    }


    const getServices = (sel) => {


        let r_code = [];
        sel && sel.map((s, i) => {
            r_code.push(s.value);
        })
        let codes = r_code.filter(onlyUnique);

        axios
            .post(`/api/admin/invoice-jobs`, { codes }, { headers })
            .then((res) => {

                let resp = res.data.services;
                if (resp.length > 0) {
                    setjServices(resp);
                    setTimeout(() => {
                        let st = 0;
                        for (let r in resp) {
                            $('.job_hour' + r).val(resp[r].job_hour);
                            $('.price' + r).val(resp[r].total);
                            st += parseFloat(resp[r].total);
                        }
                        $('.subtotal').val(st);
                        document.querySelector('.total').innerHTML = (st);
                    }, 500);
                }

            })

    }

    const changePrice = (e) => {

        setTimeout(() => {

            let pa = document.querySelectorAll('input[name="price"]');
            let st = 0;
            pa.forEach((e, i) => {
                if (e.value == null) { e.value = 0 }
                st += parseFloat(e.value);
            });
            $('.subtotal').val(st);
            document.querySelector('.total').innerHTML = (st);
            let t = $('.taxper').val();
            let tx = (t != undefined) ? t : 0;
            changeTax(tx);

        }, 200);
    }

    const changeTax = (tax) => {

        let sub = document.querySelector('.subtotal').value;
        document.querySelector('.total').innerHTML = 0;
        if (sub == '') { alert.error('Please enter subtotal'); return; }
        let ta = Math.ceil(((tax / 100) * sub));
        setTaxAmount(ta);
        document.querySelector('.total').innerHTML = parseFloat(ta) + parseFloat(sub);

    }


    const handleSubmit = (e) => {
        e.preventDefault();

        if (lng == undefined) { window.alert('client language is not set!'); return; }

        if (customer == null) { alert.error('Please select customer'); return; }
        if (jservices == null) { alert.error('Please select job'); return; }

        const sdata = [];
        jservices.forEach((js, i) => {

            sdata.push(
                {
                    'id': js.id,
                    'service': (js.name != undefined) ? ((lng == 'en') ? js.name : js.heb_name) : js.service,
                    'description': $('.description' + i).val(),
                    'job_hour': $('.job_hour' + i).val(),
                    'price': $('.price' + i).val(),
                }
            )
        });


        const data = {
            customer: customer,
            job: JSON.stringify(selectedJobs),
            services: (JSON.stringify(sdata)),
            due_date: (dueDate != undefined) ? dueDate : '',
            subtotal: parseFloat($('.subtotal').val()),
            taxper: (taxper != undefined) ? taxper : 0,
            total_tax: (taxAmount != undefined) ? parseFloat(taxAmount) : 0,
            amount: parseFloat($('.total').text()),
            status: 'pending'

        }

        axios.post(`/api/admin/add-invoice`, { data }, { headers })
            .then((res) => {
                alert.success('Invoice created successfully');
                setTimeout(() => {
                    navigate('/admin/invoices');
                }, 1000);
            })

    }


    useEffect(() => {
        getCustomers();
        setTimeout(() => {
            const cus = $('.cus').val();
            axios.get(`/api/admin/clients/${1}`, { headers }).then((res) => { setLng(res.data.client.lng) });
        }, 1000);
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title addEmployer">Create Invoice</h1>
                    <div className="card card-body">
                        <form>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Customer
                                        </label>
                                        <select className='form-control' onChange={(e) => { setCustomer(e.target.value); clientOrders(e.target.value) }}>
                                            <option value={0}>-- select customer --</option>
                                            {
                                                clients && clients.map((c, i) => {
                                                    return (<option value={c.id}>{c.firstname + " " + c.lastname}</option>);
                                                })
                                            }

                                        </select>

                                    </div>

                                    <div className="form-group">
                                        <label className="control-label">
                                            Document Type
                                        </label>
                                        <select className='form-control'>
                                            <option value={0}>-- select document --</option>
                                            <option value="invoice">Invoice</option>
                                            <option value="invrec">Invoice Receipt( Payment deduct from save card )</option>
                                        </select>

                                    </div>

                                     

                                         <div className="form-group">
                                            <label className="control-label">
                                                Orders
                                            </label>
                                            <Select
                                                isMulti
                                                name="colors"
                                                options={cjobs}
                                                className="basic-multi-single"
                                                isClearable={true}
                                                value={selectedJobs}
                                                classNamePrefix="select"
                                                onChange={(e) => { setSelectedJobs(e); getServices(e); }}
                                            />

                                        </div>
                                    </div>

                                    {
                                        jservices && jservices.map((js, i) => {

                                            return (
                                                <>

                                                    <div className="row col-sm-12" style={{ "margin": "3px" }}>

                                                        <div className=''>
                                                            <span className="hpoint">&#9755;</span>
                                                        </div>

                                                        <div className='col-sm-3'>
                                                            <div className="form-group">
                                                                <label className="control-label">
                                                                    Service
                                                                </label>
                                                                <input
                                                                    type="text"
                                                                    readOnly
                                                                    name="service"
                                                                    value={js.name}
                                                                    className="form-control"
                                                                    placeholder="Service"
                                                                    required
                                                                />

                                                            </div>
                                                        </div>
                                                        <div className='col-sm-3'>
                                                            <div className="form-group">
                                                                <label className="control-label">
                                                                    Description
                                                                </label>
                                                                <textarea
                                                                    name="description"
                                                                    className={`form-control description` + i}
                                                                    placeholder="Description"

                                                                />

                                                            </div>
                                                        </div>
                                                        <div className='col-sm-3'>
                                                            <div className="form-group">
                                                                <label className="control-label">
                                                                    Job Hours
                                                                </label>
                                                                <input
                                                                    type="number"
                                                                    name="hours"
                                                                    className={`form-control job_hour` + i}
                                                                    placeholder="Job Hours"
                                                                    required
                                                                />

                                                            </div>
                                                        </div>
                                                        <div className='col-sm-2'>
                                                            <div className="form-group">
                                                                <label className="control-label">
                                                                    Price
                                                                </label>
                                                                <input
                                                                    type="number"
                                                                    name="price"
                                                                    onChange={(e) => changePrice(e)}
                                                                    className={`form-control price` + i}
                                                                    placeholder="Price"
                                                                    required
                                                                />

                                                            </div>
                                                        </div>
                                                    </div>
                                                </>
                                            )
                                        })

                                    }

                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Due Date
                                            </label>
                                            <input
                                                type="date"
                                                onChange={(e) =>
                                                    setDueDate(e.target.value)
                                                }
                                                name="dueDate"
                                                className="form-control"
                                                placeholder="Enter cycle count"
                                                required
                                            />

                                        </div>
                                    </div>

                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Subtotal
                                            </label>
                                            <input
                                                type="number"
                                                onChange={(e) =>
                                                    setSubtotal(e.target.value)
                                                }
                                                className="form-control subtotal"
                                                placeholder="Subtotal"
                                                required
                                            />

                                        </div>
                                    </div>

                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Tax percentage
                                            </label>
                                            <input
                                                type="number"
                                                onChange={(e) => {
                                                    setTaxPer(e.target.value);
                                                    changeTax(e.target.value);
                                                }

                                                }
                                                className="form-control taxper"
                                                placeholder="Tax %"
                                                required
                                            />

                                        </div>
                                    </div>

                                    <div className="col-sm-12">
                                        <div className="form-group">

                                            <h5>Total Payable Amount : <span className="total"></span></h5>

                                        </div>
                                    </div>



                                    <div className="form-group text-center col-sm-12">
                                        <input
                                            type="submit"
                                            value="SAVE"
                                            onClick={handleSubmit}
                                            className="btn btn-pink saveBtn"
                                        />
                                    </div>
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    )
}