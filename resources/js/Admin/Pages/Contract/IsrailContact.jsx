import React, { useEffect, useRef, useState } from "react";
import { Table } from "react-bootstrap";
import * as yup from "yup";
import { useFormik } from "formik";
import SignatureCanvas from "react-signature-canvas";

import TextField from "../../../Pages/Form101/inputElements/TextField";
import DateField from "../../../Pages/Form101/inputElements/DateField";

const initialValues = {
    fullName: "",
    IdNumber: "",
    Address: "",
    startDate: "",
    signatureDate: "",
    PhoneNo: "",
    MobileNo: "",
    signature: "",
    role: "",
};

const formSchema = yup.object({
    fullName: yup.string().trim().required("Full name is required"),
    role: yup.string().trim().required("Role is required"),
    IdNumber: yup
        .number()
        .typeError("invalid number")
        .required("Id Number is required"),
    Address: yup.string().trim().required("Address  is required"),
    startDate: yup.date().required("Start date of job is required"),
    signatureDate: yup.date().required("Date  is required"),
    PhoneNo: yup
        .string()
        .trim()
        .matches(/^\d{10}$/, "Invalid phone number")
        .required("Phone number is required"),
    MobileNo: yup
        .string()
        .trim()
        .matches(/^\d{10}$/, "Invalid mobile number")
        .required("Mobile number is required"),
    signature: yup.mixed().required("Signature is required"),
});
export function IsrailContact({
    handleFormSubmit,
    workerDetail,
    workerFormDetails,
    checkFormDetails,
}) {
    const sigRef = useRef();
    const [formValues, setFormValues] = useState(null);
    const [firstNameReadOnly, setFirstNameReadOnly] = useState(false);
    const [idNumberReadOnly, setIdNumberReadOnly] = useState(false);
    const [addressReadOnly, setAddressReadOnly] = useState(false);
    const [phoneNoReadOnly, setPhoneNoReadOnly] = useState(false);
    const {
        errors,
        touched,
        handleBlur,
        handleChange,
        handleSubmit,
        values,
        setFieldValue,
        isSubmitting,
    } = useFormik({
        initialValues: formValues ?? initialValues,
        enableReinitialize: true,
        validationSchema: formSchema,
        onSubmit: (values) => {
            handleFormSubmit(values);
        },
    });

    useEffect(() => {
        if (checkFormDetails) {
            setFormValues(workerFormDetails);
            disableInputs();            
        }else{
            setFieldValue("fullName",workerDetail.firstname +' '+workerDetail.lastname);
            setFieldValue("IdNumber",workerDetail.worker_id);
            setFieldValue("Address",workerDetail.address);
            setFieldValue("PhoneNo",workerDetail.phone);
            setFirstNameReadOnly(true);
            setIdNumberReadOnly(true);
            setAddressReadOnly(true);
            setPhoneNoReadOnly(true);
        }
    }, [checkFormDetails,workerFormDetails,workerDetail]);

    const disableInputs = () => {
        const inputs = document.querySelectorAll(".targetDiv input");
        inputs.forEach((input) => {
            input.disabled = true;
        });
    };

    const handleSignatureEnd = () => {
        setFieldValue("signature", sigRef.current.toDataURL());
    };
    const clearSignature = () => {
        sigRef.current.clear();
        setFieldValue("signature", "");
    };
    return (
        <div className="container targetDiv">
            <div id="content">
                <div className="w-75 mx-auto mt-5">
                    <form onSubmit={handleSubmit}>
                        <div className="text-center">
                            <h5>
                                <strong>
                                    <u>
                                        Notice to the employee regarding details
                                        of working conditions
                                    </u>
                                </strong>
                            </h5>

                            <p className="mt-2">
                                In accordance with the Notification to the
                                Employee (Working Conditions) Law 2002
                            </p>
                        </div>
                        <div>
                            <ol
                                className="mt-5 lh-lg "
                                style={{ fontSize: "16px" }}
                            >
                                <li>
                                    <strong>
                                        Name of employer: Brom Service L.M. Ltd.
                                        - private company number 515184208
                                    </strong>
                                    <div className="row gap-3">
                                        <div className="col-6">
                                            <TextField
                                                name={"fullName"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={" Name of the employee"}
                                                value={values.fullName}
                                                required={true}
                                                readonly={firstNameReadOnly}
                                                error={
                                                    touched.fullName &&
                                                    errors.fullName
                                                }
                                            />
                                        </div>
                                        <div className="col-6">
                                            <TextField
                                                name={"IdNumber"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={" ID Number"}
                                                value={values.IdNumber}
                                                required={true}
                                                readonly={idNumberReadOnly}
                                                error={
                                                    touched.IdNumber &&
                                                    errors.IdNumber
                                                }
                                            />
                                        </div>
                                    </div>
                                    <TextField
                                        name={"Address"}
                                        onBlur={handleBlur}
                                        onChange={handleChange}
                                        label={"Address"}
                                        value={values.Address}
                                        readonly={addressReadOnly}
                                        required={true}
                                        error={
                                            touched.Address && errors.Address
                                        }
                                    />
                                </li>
                                <li>
                                    <DateField
                                        name={"startDate"}
                                        onBlur={handleBlur}
                                        onChange={handleChange}
                                        label={" Start date of job:"}
                                        value={values.startDate}
                                        required={true}
                                        error={
                                            touched.startDate &&
                                            errors.startDate
                                        }
                                    />
                                    <div className="row">
                                        <div className="col-6">
                                            <TextField
                                                name={"PhoneNo"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={"Home phone number:"}
                                                value={values.PhoneNo}
                                                required={true}
                                                readonly={phoneNoReadOnly}
                                                error={
                                                    touched.PhoneNo &&
                                                    errors.PhoneNo
                                                }
                                            />
                                        </div>
                                        <div className="col-6">
                                            <TextField
                                                name={"MobileNo"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={"Mobile number:"}
                                                value={values.MobileNo}
                                                required={true}
                                                error={
                                                    touched.MobileNo &&
                                                    errors.MobileNo
                                                }
                                            />
                                        </div>
                                    </div>

                                    <p>
                                        <strong>
                                            Contract period: The contract period
                                            is not fixed
                                        </strong>
                                    </p>
                                </li>
                                <li>
                                    The main duties of the employee are as
                                    follows:
                                    <TextField
                                        name={"role"}
                                        onBlur={handleBlur}
                                        onChange={handleChange}
                                        label={"Role"}
                                        value={values.role}
                                        required={true}
                                        error={touched.role && errors.role}
                                    />
                                </li>
                                <li>
                                    The name of the direct supervisorof the
                                    employee or the job title of the direct
                                    supervisor: Full name: Alex Kanev
                                </li>
                                <li>
                                    <p>
                                        The basic salary of the cleaning workers
                                        will be calculated based on the minimum
                                        wage according to law, and in addition
                                        they will be given a bonus for
                                        perseverance, which will supplement
                                        their salary to a basic salary of NIS 45
                                        per hour plus social conditions, and
                                        this as a reward for perseverance at
                                        work for 3 full months in a row in the
                                        company.
                                    </p>
                                    <p>
                                        If the employee decides to leave the job
                                        before the end of the full three-month
                                        period, as agreed upon in this
                                        agreement, the employer will be entitled
                                        to deduct the amount of the bonus that
                                        exceeds the minimum wage paid to the
                                        employee, and even require the employee
                                        to return the amount of the bonus
                                        already paid to him during the first
                                        three months of work, and often by of
                                        deducting the excess amount from his
                                        salary or from any other payment due to
                                        him. The employee's salary is the
                                        industry minimum wage according to the
                                        collective agreement in the cleaning
                                        industry, which currently amounts to
                                        NIS 32.3 per hour.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        A breakdown of all the payments that
                                        will be paid to the employee as wages
                                        are as follows:
                                    </p>
                                    <Table bordered className=" mt-3" size="sm">
                                        <thead className="text-center">
                                            <tr>
                                                <th colSpan={2}>
                                                    Payments that are not fixed
                                                </th>
                                                <th colSpan={2}>
                                                    Regular payments
                                                </th>
                                            </tr>
                                            <tr>
                                                <th>Payment Date</th>
                                                <th>Payment type</th>
                                                <th>Payment Date</th>
                                                <th>Payment type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    According to the customer's
                                                    custom of charging the value
                                                    of meals
                                                </td>
                                                <td>Subsidized meals</td>
                                                <td>B–9 per month</td>
                                                <td>Salary</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    In accordance with the
                                                    collective agreement in the
                                                    cleaning industry
                                                </td>
                                                <td>
                                                    Leave for a family party for
                                                    cleaning workers
                                                </td>
                                                <td>
                                                    On the 9th of the month,
                                                    except in cases where the
                                                    employee will use
                                                    transportation, in which
                                                    case he is not entitled to
                                                    travel reimbursement.
                                                </td>
                                                <td>Travel</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    In accordance with the law
                                                    and the collective agreement
                                                    in the cleaning industry and
                                                    according to the leave
                                                    procedure at the employer.
                                                </td>
                                                <td>
                                                    Vacation for cleaning
                                                    workers
                                                </td>
                                                <td>On the 9th of the month</td>
                                                <td>
                                                    recovery (hourly worker)
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    In the month in which the
                                                    holidays of Tishrei and
                                                    Sivan fall (usually October
                                                    and April)
                                                </td>
                                                <td>
                                                    A holiday gift for cleaning
                                                    workers
                                                </td>
                                                <td>annual</td>
                                                <td>
                                                    recovery (monthly employee)
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    In accordance with the law
                                                    on sick pay and the
                                                    collective agreement in the
                                                    cleaning industry
                                                </td>
                                                <td>
                                                    Sick pay for cleaning
                                                    workers
                                                </td>
                                                <td>after a year of work</td>
                                                <td>
                                                    Seniority bonus for cleaning
                                                    workers
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    In accordance with the
                                                    collective agreement in the
                                                    cleaning industry
                                                </td>
                                                <td>
                                                    Holiday pay for cleaning
                                                    workers
                                                </td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </Table>
                                </li>

                                <li>
                                    The employee's rights, including time off,
                                    recovery, illness, holidays, etc., are
                                    calculated and paid according to the scope
                                    of the actual position and in accordance
                                    with the relevant legal provisions.
                                </li>
                                <li>
                                    Reimbursement of travel expenses in
                                    accordance with the provisions of the
                                    general collective agreement, and according
                                    to the cheapest cost in public
                                    transportation: 11 NIS per day or a maximum
                                    of 225 NIS monthly free for a full month of
                                    work.
                                </li>
                                <li>
                                    Hereby, I agree to deduct the wages of
                                    participation in meals/the value of meals
                                    according to the custom at the site where I
                                    work (to be circled).
                                </li>
                                <li>
                                    The length of the employee's normal working
                                    day is: 8 hours per day (6 days) / 8.4 hours
                                    per day (5 days) / beyond that he will be
                                    paid additional hours in accordance with the
                                    law. It is known to the employee that during
                                    a shift of over 6 hours he went on an unpaid
                                    meal break.
                                    <p>
                                        The length of the employee's normal work
                                        week is 42 hours as well as: 5 days / 6
                                        days / according to the work arrangement
                                        that will be determined in advance
                                    </p>
                                </li>
                                <li>
                                    The employee's weekly day of rest: for a
                                    Jewish employee: Saturday / day of rest for
                                    a non-Jewish employee if he
                                    chooses: Sunday or
                                </li>
                                <li>
                                    As part of your work at the company, the
                                    company will be entitled to place you on
                                    various sites at its discretion and
                                    according to the regional service manager.
                                    The service manager as well as the
                                    operations manager will be at your disposal
                                    at any time for any question /
                                    ambiguity on any subject
                                </li>
                                <li>
                                    {" "}
                                    Payments for social conditions to which the
                                    employee is entitled shall be paid according
                                    to the expansion orders and the relevant
                                    collective agreements applicable to him.
                                    <p>
                                        The type of payment:
                                        flashlights/insurance, etc. Training.
                                        The receiving body and the name of the
                                        program: according to the employee's
                                        choice and in accordance with the
                                        provisions of the expansion
                                        order/collective agreement applicable
                                        to the employee.
                                    </p>
                                    <Table bordered size="sm" className=" mt-3">
                                        <thead className="text-center">
                                            <tr>
                                                <th>Payment start date</th>
                                                <th>employer's provision %</th>
                                                <th>
                                                    deductions from the employee
                                                    %
                                                </th>
                                                <th>
                                                    The receiving body and the
                                                    name of the program
                                                </th>
                                                <th>Payment Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    from the actual start date
                                                    of the work
                                                </td>
                                                <td>
                                                    7.5% rewards + 8.33%
                                                    severance pay (according to
                                                    the collective agreement in
                                                    the cleaning industry)
                                                </td>
                                                <td>7%</td>
                                                <td>
                                                    "Meitav Dash" POS (unless
                                                    otherwise stated on the
                                                    payslip) unless the employee
                                                    requested otherwise
                                                </td>
                                                <td>Pension provisions</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    from the actual start date
                                                    of the work
                                                </td>
                                                <td>
                                                    7.5% rewards + 6% severance
                                                    pay (according to the
                                                    collective agreement in the
                                                    cleaning industry)
                                                </td>
                                                <td>7%</td>
                                                <td>
                                                    "Meitav Dash" POS (unless
                                                    otherwise stated on the pay
                                                    slip) unless the employee
                                                    requested otherwise
                                                </td>
                                                <td>
                                                    Pension provision for a
                                                    slave working
                                                    overtime/rest day
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    from the actual start date
                                                    of the work
                                                </td>
                                                <td>
                                                    5% (in accordance with the
                                                    collective agreement in the
                                                    cleaning industry)
                                                </td>
                                                <td>5%</td>
                                                <td>
                                                    "Meitav Dash" POS (unless
                                                    otherwise stated on the
                                                    payslip) unless the employee
                                                    requested otherwise
                                                </td>
                                                <td>
                                                    Pension provision in favor
                                                    of travel
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    from the actual start date
                                                    of the work
                                                </td>
                                                <td>
                                                    7.5% (in accordance with the
                                                    collective agreement in the
                                                    cleaning industry)
                                                </td>
                                                <td>2.5%</td>
                                                <td>
                                                    "Meitav Dash" POS (unless
                                                    otherwise stated on the
                                                    payslip) unless the employee
                                                    requested otherwise
                                                </td>
                                                <td>
                                                    Appropriation for a further
                                                    education fund
                                                </td>
                                            </tr>
                                        </tbody>
                                    </Table>
                                    <p>
                                        You are requested to notify your direct
                                        supervisor in writing of the name of the
                                        fund and/or training and/or pension fund
                                        you wish to join, by the end of the
                                        first month of your employment. I am
                                        aware that as long as I terminate my
                                        employment without giving the company
                                        prior notice, the company will exercise
                                        its right by law to offset the payment
                                        of the notice from any amount due to me,
                                        which includes vacation pay and various
                                        account settlement payments.
                                    </p>
                                </li>
                                <li>
                                    The name of the workers' organization that
                                    is a party to that collective agreement that
                                    regulates the worker's working conditions
                                    is: the Jerusalem Chamber of Commerce. This
                                    notice is a notice from the employer
                                    regarding the main working conditions:
                                    nothing in this notice is intended to
                                    derogate from any right to an employee by
                                    virtue of any law, expansion order,
                                    collective agreement or employment contract.
                                </li>
                            </ol>
                            <div className="row mt-3">
                                <div className="col-4">
                                    <p>
                                        <strong>
                                            The employee's signature:*
                                        </strong>
                                        <span className="text-danger">
                                            {touched.signature &&
                                                errors.signature}
                                        </span>
                                    </p>
                                    {formValues && formValues.signature ? (
                                        <img src={formValues.signature} />
                                    ) : (
                                        <>
                                            <SignatureCanvas
                                                penColor="black"
                                                canvasProps={{
                                                    className:
                                                        "sign101 border mt-1",
                                                }}
                                                ref={sigRef}
                                                onEnd={handleSignatureEnd}
                                            />

                                            <div className="d-block">
                                                <button
                                                    className="btn btn-warning mb-2"
                                                    type="button"
                                                    onClick={clearSignature}
                                                >
                                                    Clear
                                                </button>
                                            </div>
                                        </>
                                    )}
                                </div>
                                <div className="col-5"></div>
                                <div className="col-3">
                                    <DateField
                                        name={"signatureDate"}
                                        onBlur={handleBlur}
                                        onChange={handleChange}
                                        label={"Date"}
                                        value={values.signatureDate}
                                        required={true}
                                        error={
                                            touched.signatureDate &&
                                            errors.signatureDate
                                        }
                                    />
                                </div>
                            </div>
                            <p className="text-right">
                                (What is stated in the singular and/or masculine
                                in this document is also feminine in meaning)
                            </p>
                            <div className="text-center mt-5">
                                <p>Best regards</p>
                                <strong>Broom Service L.M. Ltd</strong>
                            </div>
                        </div>
                        {!formValues && (
                            <button
                                className="btn btn-success mt-3"
                                type="submit"
                                disabled={isSubmitting}
                            >
                                Submit
                            </button>
                        )}
                    </form>
                </div>
            </div>
        </div>
    );
}
